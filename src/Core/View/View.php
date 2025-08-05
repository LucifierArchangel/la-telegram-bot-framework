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
        return $this->message->run($messageParams);
    }

    protected function buildKeyboard(array $keyboardParams, bool $isCallback)
    {
        if (!isset($this->keyboard)) {
            return null;
        }

        $keyboardData = $this->keyboard->build($keyboardParams);
        $keyboardType = $this->keyboard->getType();

        if ($keyboardType === 'inline') {
            return new InlineKeyboardMarkup($keyboardData);
        }

        return new ReplyKeyboardMarkup($keyboardData, false, true);
    }

    protected function handleCallback(array $context): bool
    {
        if (!$context['callback']) {
            return true;
        }

        try {
            $this->bot->answerCallbackQuery($context['currentMessage']->getId());
        } catch (\Exception $exception) {
            error_log("[WARNING] Failed to answer callback query: " . $exception->getMessage());
        }

        return true;
    }

    protected function createMediaObject(array $media, ?string $text)
    {
        $mediaTypes = [
            'photo'    => InputMediaPhoto::class,
            'video'    => InputMediaVideo::class,
            'document' => InputMediaDocument::class,
            'gif'      => InputMediaAnimation::class
        ];

        foreach ($mediaTypes as $type => $className) {
            if (isset($media[$type])) {
                $inputMedia = new $className();
                $inputMedia->setType($type === 'gif' ? 'animation' : $type);
                $inputMedia->setMedia($media[$type]);

                if ($text !== null) {
                    $inputMedia->setCaption($text);
                    $inputMedia->setParseMode('HTML');
                }

                return $inputMedia;
            }
        }

        return null;
    }

    protected function tryEditMessage(array $context, ?string $text, $keyboard, array $media): bool
    {
        $chatId = $context['chatId'];
        $msgId = $context['msgId'];

        if (!is_int($msgId) || empty($chatId)) {
            error_log("[ERROR] Invalid message or chat ID for editing");
            return false;
        }

        try {
            $originalMessage = $context['currentMessage']->getMessage();
            $hasOriginalMedia = $originalMessage && (
                    $originalMessage->getPhoto() ||
                    $originalMessage->getVideo() ||
                    $originalMessage->getDocument() ||
                    $originalMessage->getAnimation()
                );

            if (!empty($media) || $hasOriginalMedia) {
                $inputMedia = $this->createMediaObject($media, $text);
                if ($inputMedia) {
                    $this->bot->editMessageMedia($chatId, $msgId, $inputMedia, null, $keyboard);
                    return true;
                }

                if ($text !== null && !$hasOriginalMedia) {
                    $this->bot->editMessageText($chatId, $msgId, $text, 'HTML',
                        $this->message->getPreview(), $keyboard);
                    return true;
                } elseif ($hasOriginalMedia) {
                    $this->bot->editMessageReplyMarkup($chatId, $msgId, $keyboard);
                    return true;
                }

                return false;
            }

            if ($text !== null) {
                $this->bot->editMessageText($chatId, $msgId, $text, 'HTML',
                    $this->message->getPreview(), $keyboard);
                return true;
            }

            $this->bot->editMessageReplyMarkup($chatId, $msgId, $keyboard);
            return true;

        } catch (\Exception $exception) {
            error_log("[ERROR] Failed to edit message: " . $exception->getMessage());

            try {
                $this->bot->editMessageReplyMarkup($chatId, $msgId, $keyboard);
                return true;
            } catch (\Exception $e) {
                error_log("[ERROR] Failed to edit markup: " . $e->getMessage());
            }
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
                in_array($chatMember->getStatus(), ['administrator', 'creator'])
            ) {
                $this->bot->deleteMessage($chatId, $msgId);
                return true;
            } else {
                $this->isDeleted = false;
                if ($messageAge >= self::MAX_MESSAGE_AGE) {
                    error_log("[INFO] Message too old to delete (age: {$messageAge}s)");
                } else {
                    error_log("[INFO] No permission to delete in chat {$chatId}");
                }
            }
        } catch (\Exception $exception) {
            error_log("[ERROR] Failed to delete message: " . $exception->getMessage());
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
                $this->bot->sendPhoto($chatId, $media['photo'], $text, null, $keyboard, false, "HTML");
            } elseif (isset($media['video'])) {
                $this->bot->sendVideo($chatId, $media['video'], null, $text, null, $keyboard, false, false, "HTML");
            } elseif (isset($media['gif'])) {
                $this->bot->sendAnimation($chatId, $media['gif'], null, $text, null, $keyboard, false, "HTML");
            } elseif (isset($media['document'])) {
                $this->bot->sendDocument($chatId, $media['document'], $text, null, $keyboard, false, "HTML");
            } elseif (empty($media) && $text !== null) {
                $this->bot->sendMessage($chatId, $text, "HTML", $this->message->getPreview(), null, $keyboard);
            }

            return true;
        } catch (\Exception $e) {
            error_log("[ERROR] Failed to send message: " . $e->getMessage());
            return false;
        }
    }

    public function show(
        $message = [],
        $keyboard = [],
        $media = [],
        $tryToEdit = true
    ): bool {
        $context = $this->getMessageContext();

        if (empty($context['chatId'])) {
            return false;
        }

        if ($context['callback'] && !$this->handleCallback($context)) {
            return false;
        }

        $this->configure();
        $text = $this->prepareMessageText($message);
        $keyboardMarkup = $this->buildKeyboard($keyboard, $context['callback']);

        if ($this->message->getType() === 'send') {
            $isInline = $keyboardMarkup instanceof InlineKeyboardMarkup;

            if ($tryToEdit && $isInline && $this->canEditMessage($context, $keyboardMarkup)) {
                if ($this->tryEditMessage($context, $text, $keyboardMarkup, $media)) {
                    return true;
                }
            }

            if ($tryToEdit && $context['callback'] && $this->shouldDeleteMessage($context)) {
                $this->tryDeleteMessage($context);
            }

            return $this->sendNewMessage($context, $text, $keyboardMarkup, $media);
        }

        return true;
    }
}
