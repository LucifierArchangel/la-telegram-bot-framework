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

    private const MAX_MESSAGE_AGE = 172800;

    public function __construct(Update $update, Client $bot)
    {
        $this->update = $update;
        $this->bot = $bot;
    }

    public function configure()
    {

    }

    protected function getMessageContext(): array
    {
        $currentMessage = $this->update->getMessage();
        $chatId = null;
        $msgId = null;
        $callback = false;

        if (isset($currentMessage)) {
            $chatId = $currentMessage->getChat()->getId();
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
            } elseif ($text !== null) {
                $this->bot->editMessageText(
                    $chatId,
                    $msgId,
                    $text,
                    'HTML',
                    $this->message->getPreview(),
                    $keyboard
                );
                return true;
            } else {
                $this->bot->editMessageReplyMarkup(
                    $chatId,
                    $msgId,
                    $keyboard
                );
                return true;
            }
        } catch (\Exception $exception) {
            error_log("[ERROR] error when editing message ID: $msgId in chat: $chatId. Error: "
                . $exception->getMessage());
            error_log("[DEBUG] Exception trace: " . $exception->getTraceAsString());
        }

        return false;
    }

    protected function canEditMessage(array $context, $keyboard): bool
    {
        return $context['callback'] &&
            isset($this->keyboard) &&
            $this->keyboard->getType() === 'inline' &&
            is_int($context['msgId']) &&
            !empty($context['chatId']);
    }

    protected function shouldDeleteMessage(array $context): bool
    {
        return $this->isDeleted === true &&
            is_int($context['msgId']) &&
            !empty($context['chatId']);
    }

    protected function getBotInfo()
    {
        if (!isset($this->botCache['me'])) {
            $this->botCache['me'] = $this->bot->getMe();
        }
        return $this->botCache['me'];
    }

    protected function tryDeleteMessage(array $context): bool
    {
        $chatId = $context['chatId'];
        $msgId = $context['msgId'];

        try {
            $me = $this->getBotInfo();
            $chatMember = $this->bot->getChatMember($chatId, $me->getId());

            $messageTime = $context['currentMessage']->getMessage()?->getDate();
            if (!$messageTime) {
                return false;
            }

            $messageAge = time() - $messageTime;

            if ($messageAge < self::MAX_MESSAGE_AGE &&
                ($chatMember->getStatus() === 'administrator' || $chatMember->getStatus() === 'creator')
            ) {
                $this->bot->deleteMessage($chatId, $msgId);
                return true;
            } else {
                $this->isDeleted = false;
                if ($messageAge >= self::MAX_MESSAGE_AGE) {
                    error_log("[INFO] Message ID: $msgId is too old to delete (age: $messageAge seconds)");
                } else {
                    error_log("[INFO] Bot doesn't have permission to delete messages in chat $chatId");
                }
            }
        } catch (\Exception $exception) {
            error_log("[ERROR] error when deleting message ID: $msgId in chat: $chatId. Error: "
                . $exception->getMessage());
            $this->isDeleted = false;
        }

        return false;
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
        $media = []
    ): bool {
        $context = $this->getMessageContext();

        if (empty($context['chatId'])) {
            return false;
        }

        $this->configure();

        $text = $this->prepareMessageText($message);

        $keyboardMarkup = $this->buildKeyboard($keyboard, $context['callback']);

        if ($context['callback']) {
            $this->handleCallback($context);
        }

        if ($this->message->getType() === 'send') {
            if ($this->canEditMessage($context, $keyboardMarkup)) {
                if ($this->tryEditMessage($context, $text, $keyboardMarkup, $media)) {
                    return true;
                }
            }

            if ($context['callback'] && $this->shouldDeleteMessage($context)) {
                $this->tryDeleteMessage($context);
            }

            return $this->sendNewMessage($context, $text, $keyboardMarkup, $media);
        }

        return true;
    }
}
