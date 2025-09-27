<?php

namespace Lucifier\Framework\Core\View;

use Lucifier\Framework\Keyboard\Keyboard;
use Lucifier\Framework\Message\Message;
use TelegramBot\Api\Client;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use TelegramBot\Api\Types\InputMedia\InputMediaPhoto;
use TelegramBot\Api\Types\InputMedia\InputMediaVideo;
use TelegramBot\Api\Types\InputMedia\InputMediaDocument;
use TelegramBot\Api\Types\InputMedia\InputMediaAnimation;
use TelegramBot\Api\Types\ReplyKeyboardMarkup;
use TelegramBot\Api\Types\Update;

class View
{
    protected Message $message;

    protected Keyboard $keyboard;

    protected Client $bot;

    protected Update $update;

    protected bool $isDeleted = true;

    protected array $botCache = [];

    protected bool $forceNew = false;

    private const MAX_MESSAGE_AGE = 172800;

    public function __construct(Update $update, Client $bot, $forceNew = false)
    {
        $this->update = $update;
        $this->bot = $bot;
        $this->forceNew = $forceNew;
    }

    public function configure()
    {

    }

    /**
     * Установить флаг принудительной отправки нового сообщения
     */
    public function setForceNew(bool $forceNew): self
    {
        $this->forceNew = $forceNew;
        return $this;
    }

    /**
     * Получить флаг принудительной отправки нового сообщения
     */
    public function isForceNew(): bool
    {
        return $this->forceNew;
    }

    protected function getMessageContext(): array
    {
        $currentMessage = $this->update->getMessage();
        $chatId = null;
        $msgId = null;
        $callback = false;

        if (isset($currentMessage)) {
            $chatId = $currentMessage->getChat()->getId();
            $msgId = $currentMessage->getMessageId();
        } else {
            $callback = true;
            $currentMessage = $this->update->getCallbackQuery();
            if (isset($currentMessage)) {
                $msgId = $currentMessage->getMessage()?->getMessageId();
                $chatId = $currentMessage->getMessage()?->getChat()?->getId();
            }
        }

        return [
            'currentMessage' => $currentMessage,
            'chatId'         => $chatId,
            'msgId'          => $msgId,
            'callback'       => $callback
        ];
    }

    protected function prepareMessageText(array $messageParams): ?string
    {
        $text = $this->message->run($messageParams);
        return $text;
    }

    protected function buildKeyboard(array $keyboardParams, bool $isCallback)
    {
        if (!isset($this->keyboard)) {
            return null;
        }

        $keyboardData = $this->keyboard->build($keyboardParams);
        $keyboardType = $this->keyboard->getType();

        if (
            $isCallback
            && $keyboardType !== 'inline'
        ) {
            return null;
        }

        if ($keyboardType === 'inline') {
            return new InlineKeyboardMarkup($keyboardData);
        }

        return new ReplyKeyboardMarkup($keyboardData, false, true);
    }

    protected function handleCallback(array $context): bool
    {
        if (!$context['callback']) {
            return false;
        }

        try {
            $this->bot->answerCallbackQuery($context['currentMessage']->getId());
            return true;
        } catch (\Exception $exception) {
            error_log("[ERROR] error when answering callback query: " . $exception->getMessage());
        }

        return false;
    }

    protected function createMediaObject(array $media, ?string $text)
    {
        if (isset($media['photo'])) {
            $inputMedia = new InputMediaPhoto();

            $inputMedia->setType('photo');
            $inputMedia->setMedia($media['photo']);

            if ($text !== null) {
                $inputMedia->setCaption($text);
                $inputMedia->setParseMode('HTML');
            }

            return $inputMedia;

        }

        if (isset($media['video'])) {
            $inputMedia = new InputMediaVideo();

            $inputMedia->setType('video');
            $inputMedia->setMedia($media['video']);

            if ($text !== null) {
                $inputMedia->setCaption($text);
                $inputMedia->setParseMode('HTML');
            }
            return $inputMedia;

        }

        if (isset($media['document'])) {
            $inputMedia = new InputMediaDocument();

            $inputMedia->setType('document');
            $inputMedia->setMedia($media['document']);

            if ($text !== null) {
                $inputMedia->setCaption($text);
                $inputMedia->setParseMode('HTML');
            }
            return $inputMedia;

        }

        if (isset($media['gif'])) {
            $inputMedia = new InputMediaAnimation();

            $inputMedia->setType('animation');
            $inputMedia->setMedia($media['gif']);

            if ($text !== null) {
                $inputMedia->setCaption($text);
                $inputMedia->setParseMode('HTML');
            }
            return $inputMedia;
        }

        return null;
    }

    protected function tryEditMessage(array $context, ?string $text, $keyboard, array $media): bool
    {
        $chatId = $context['chatId'];
        $msgId = $context['msgId'];

        if (!is_int($msgId) || empty($chatId)) {
            error_log("[ERROR] Invalid message or chat ID for editing. msgId: " . var_export($msgId, true) . ", chatId: " . var_export($chatId, true));
            return false;
        }

        try {
            if (!empty($media)) {
                $inputMedia = $this->createMediaObject($media, $text);
                if ($inputMedia) {
                    $this->bot->editMessageMedia(
                        $chatId,
                        $msgId,
                        $inputMedia,
                        null,
                        $keyboard
                    );
                    return true;
                }

                error_log("[ERROR] Failed to create media object");
                return false;
            }

            if ($text !== null) {
                $currentMessage = $context['callback']
                    ? $context['currentMessage']->getMessage()
                    : $context['currentMessage'];

                $hasMedia = $currentMessage && (
                        $currentMessage->getPhoto() ||
                        $currentMessage->getVideo() ||
                        $currentMessage->getDocument() ||
                        $currentMessage->getAnimation()
                    );

                if ($hasMedia) {
                    error_log("[DEBUG] Попытка удалить медиа из сообщения через editMessageText не поддерживается");
                    return false;
                } else {
                    $this->bot->editMessageText(
                        $chatId,
                        $msgId,
                        $text,
                        'HTML',
                        $this->message->getPreview(),
                        $keyboard
                    );
                    return true;
                }
            } else {
                $this->bot->editMessageReplyMarkup(
                    $chatId,
                    $msgId,
                    $keyboard
                );
                return true;
            }
        } catch (\Exception $exception) {
            $errorMsg = $exception->getMessage();

            if (strpos($errorMsg, 'there is no text in the message to edit') !== false) {
                error_log("[DEBUG] Попытка отредактировать сообщение без текста - в сообщении есть только медиа");
                return false;
            }

            if (strpos($errorMsg, 'message is not modified') !== false) {
                error_log("[DEBUG] Сообщение не было изменено - контент идентичен");
                return true;
            }

            if (strpos($errorMsg, 'exactly the same') !== false) {
                error_log("[DEBUG] Контент сообщения полностью идентичен");
                return true;
            }

            if (strpos($errorMsg, 'message to edit not found') !== false) {
                error_log("[DEBUG] Сообщение для редактирования не найдено - возможно уже удалено");
                return false;
            }

            if (strpos($errorMsg, "can't edit this message") !== false) {
                error_log("[DEBUG] Невозможно отредактировать это сообщение");
                return false;
            }

            error_log("[ERROR] error when editing message ID: $msgId in chat: $chatId. Error: " . $errorMsg);
            error_log("[DEBUG] Exception trace: " . $exception->getTraceAsString());
        }

        return false;
    }

    protected function canEditMessage(array $context, $keyboard): bool
    {
        if ($this->forceNew) {
            return false;
        }

        return $context['callback'] &&
            isset($this->keyboard) &&
            $this->keyboard->getType() === 'inline' &&
            is_int($context['msgId']) &&
            !empty($context['chatId']);
    }

    protected function shouldDeleteMessage(array $context): bool
    {
        if ($this->forceNew) {
            return false;
        }

        return $this->isDeleted === true &&
            is_int($context['msgId']) &&
            !empty($context['chatId']);
    }

    protected function getBotInfo()
    {
        if (!isset($this->botCache['me'])) {
            try {
                $this->botCache['me'] = $this->bot->getMe();
            } catch (\Exception $e) {
                error_log("[ERROR] Не удалось получить информацию о боте: " . $e->getMessage());
                return null;
            }
        }
        return $this->botCache['me'];
    }

    protected function tryDeleteMessage(array $context): bool
    {
        $chatId = $context['chatId'];
        $msgId = $context['msgId'];

        try {
            $messageTime = $context['currentMessage']->getMessage()?->getDate();
            if (!$messageTime) {
                error_log("[DEBUG] Не удалось получить время сообщения для удаления");
                return false;
            }

            $messageAge = time() - $messageTime;

            if ($messageAge >= self::MAX_MESSAGE_AGE) {
                $this->isDeleted = false;
                error_log("[DEBUG] Сообщение ID: $msgId слишком старое для удаления (возраст: $messageAge секунд)");
                return false;
            }

            $chat = $this->bot->getChat($chatId);
            $chatType = $chat->getType();

            if ($chatType === 'private') {
                $this->bot->deleteMessage($chatId, $msgId);
                return true;
            }

            if (in_array($chatType, ['group', 'supergroup'])) {
                $me = $this->getBotInfo();
                if (!$me) {
                    return false;
                }

                $chatMember = $this->bot->getChatMember($chatId, $me->getId());

                if ($chatMember->getStatus() === 'administrator' || $chatMember->getStatus() === 'creator') {
                    $this->bot->deleteMessage($chatId, $msgId);
                    return true;
                } else {
                    $this->isDeleted = false;
                    error_log("[DEBUG] У бота нет прав администратора в групповом чате $chatId");
                    return false;
                }
            }

            $this->bot->deleteMessage($chatId, $msgId);
            return true;

        } catch (\Exception $exception) {
            $errorMessage = $exception->getMessage();

            if (strpos($errorMessage, "can't delete this message") !== false ||
                strpos($errorMessage, "message to delete not found") !== false ||
                strpos($errorMessage, "message can't be deleted") !== false ||
                strpos($errorMessage, "doesn't have permission") !== false ||
                strpos($errorMessage, "not enough rights") !== false) {

                error_log("[DEBUG] Не удалось удалить сообщение ID: $msgId в чате: $chatId - это ожидаемо");
                $this->isDeleted = false;
                return false;
            }

            error_log("[ERROR] Неожиданная ошибка при удалении сообщения ID: $msgId в чате: $chatId. Ошибка: " . $errorMessage);
            $this->isDeleted = false;
            return false;
        }
    }

    protected function sendNewMessage(array $context, ?string $text, $keyboard, array $media): bool
    {
        $chatId = $context['chatId'];

        if (empty($chatId)) {
            return false;
        }

        try {
            if (isset($media['photo'])) {
                $this->bot->sendPhoto(
                    $chatId,
                    $media['photo'],
                    $text,
                    null,
                    $keyboard,
                    false,
                    "HTML"
                );
            } else if (isset($media['video'])) {
                $this->bot->sendVideo(
                    $chatId,
                    $media['video'],
                    null,
                    $text,
                    null,
                    $keyboard,
                    false,
                    false,
                    "HTML"
                );
            } else if (isset($media['gif'])) {
                $this->bot->sendAnimation(
                    $chatId,
                    $media['gif'],
                    null,
                    $text,
                    null,
                    $keyboard,
                    false,
                    "HTML"
                );
            } else if (isset($media['document'])) {
                $this->bot->sendDocument(
                    $chatId,
                    $media['document'],
                    $text,
                    null,
                    $keyboard,
                    false,
                    "HTML"
                );
            } else if (empty($media)) {
                $this->bot->sendMessage(
                    $chatId,
                    $text,
                    "HTML",
                    $this->message->getPreview(),
                    null,
                    $keyboard
                );
            }
            return true;
        } catch (\Exception $e) {
            error_log("[ERROR] error when sending message chatId: $chatId. Error: " . $e->getMessage());
            return false;
        }
    }

    public function show(
        $message = [],
        $keyboard = [],
        $media = [],
        $forceNew = null
    ): bool {
        $context = $this->getMessageContext();

        if (empty($context['chatId'])) {
            return false;
        }

        $shouldForceNew = $forceNew !== null ? $forceNew : $this->forceNew;

        $this->configure();

        $text = $this->prepareMessageText($message);

        $keyboardMarkup = $this->buildKeyboard($keyboard, $context['callback']);

        if ($context['callback']) {
            $this->handleCallback($context);
        }

        if ($this->message->getType() === 'send') {
            if (!$shouldForceNew) {
                if ($this->canEditMessage($context, $keyboardMarkup)) {
                    if ($this->tryEditMessage($context, $text, $keyboardMarkup, $media)) {
                        return true;
                    }
                }

                if ($context['callback'] && $this->shouldDeleteMessage($context)) {
                    $this->tryDeleteMessage($context);
                }
            }

            return $this->sendNewMessage($context, $text, $keyboardMarkup, $media);
        }

        return true;
    }
}