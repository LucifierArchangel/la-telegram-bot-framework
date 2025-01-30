<?php

namespace Lucifier\Framework\Core\View;

use http\Encoding\Stream\Inflate;
use http\Exception;
use Lucifier\Framework\Keyboard\Keyboard;
use Lucifier\Framework\Message\Message;
use TelegramBot\Api\Client;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use TelegramBot\Api\Types\InputMedia\ArrayOfInputMedia;
use TelegramBot\Api\Types\ReplyKeyboardMarkup;
use TelegramBot\Api\Types\Update;

class View
{
    /**
     * @var Message message instance for bot
     */
    protected Message $message;

    /**
     * @var Keyboard keyboard instance for bot
     */
    protected Keyboard $keyboard;

    /**
     * @var Client current bot intance
     */
    protected Client $bot;

    /**
     * @var Update current bot update object
     */
    protected Update $update;

    public function __construct($update, $bot)
    {
        $this->update = $update;
        $this->bot = $bot;
    }

    /**
     * Configure current view
     * For child class
     *
     * @return void
     */
    public function configure()
    {
    }

    /**
     * Show current view
     *
     * @param $message message's parameters array
     * @param $keyboard keyboard's parameters array
     * @return bool|string
     * @throws \Exception
     */
    public function show(
        $message = [],
        $keyboard = [],
        $media = []
    ): bool|string {
        $currentMessage = $this->update->getMessage();
        $chatId = null;
        $msgId = null;
        $callback = false;
        $canEdit = true;

        if (isset($currentMessage)) {
            $chatId = $currentMessage->getChat()->getId();
        } else {
            $callback = true;
            $currentMessage = $this->update->getCallbackQuery();
            $msgId = $currentMessage->getMessage()->getMessageId();
            if (isset($currentMessage)) {
                $chatId = $currentMessage->getMessage()->getChat()->getId();
            }
        }

        if (isset($chatId)) {
            $this->configure();

            $text = $this->fixUnclosedHtmlTags($this->message->run($message)) ?? null;
            $answerKeyboard = null;

            if (isset($this->keyboard)) {
                $answerKeyboard = $this->keyboard->build($keyboard);
                if ($callback && $this->keyboard->getType() !== 'inline') {
                    $canEdit = false;
                }
                $answerKeyboard = $this->keyboard->getType() === 'inline'
                    ? new InlineKeyboardMarkup($answerKeyboard)
                    : new ReplyKeyboardMarkup($answerKeyboard, false, true);
            }

            if ($this->message->getType() === 'send') {

                if ($msgId && $callback) {
                    $this->bot->deleteMessage($chatId, $msgId);
                }

                if ($callback) {
                    $this->bot->answerCallbackQuery($currentMessage->getId());
                }

                try {
                    if (isset($media['photo'])) {
                        $this->bot->sendPhoto(
                            $chatId,
                            $media['photo'],
                            $text,
                            null,
                            $answerKeyboard,
                            false,
                            "HTML",
                            $messageThreadId = null,
                            $protectContent = null,
                            $allowSendingWithoutReply = null);
                    }
                    if (isset($media['video'])) {
                        $this->bot->sendVideo(
                            $chatId,
                            $media['video'],
                            null,
                            $text,
                            null,
                            $answerKeyboard,
                            false,
                            false,
                            "HTML",
                            $messageThreadId = null,
                            $protectContent = null,
                            $allowSendingWithoutReply = null,
                            null);
                    }
                    if (isset($media['gif'])) {
                        $this->bot->sendAnimation(
                            $chatId,
                            $media['gif'],
                            null,
                            $text,
                            null,
                            $answerKeyboard,
                            false,
                            "HTML",
                            $messageThreadId = null,
                            $protectContent = null,
                            $allowSendingWithoutReply = null,
                            null);
                    }
                    if (isset($media['document'])) {
                        $this->bot->sendDocument(
                            $chatId,
                            $media['document'],
                            $text,
                            null,
                            $answerKeyboard,
                            false,
                            "HTML",
                            $messageThreadId = null,
                            $protectContent = null,
                            $allowSendingWithoutReply = null,
                            null
                        );
                    }
                    if (empty($media)) {
                        $this->bot->sendMessage(
                            $chatId,
                            $text,
                            "HTML",
                            $this->message->getPreview(),
                            null,
                            $answerKeyboard);
                    }
                } catch (\Exception $e) {
                    return throw new \Exception($e->getMessage());
                }
            }
        }
        return true;
    }

    private function fixUnclosedHtmlTags(string $text): string
    {
        $allowedTags = ['b', 'i', 'u', 'a', 'code', 'pre'];
        foreach ($allowedTags as $tag) {
            preg_match_all("/<$tag\b[^>]*>/i", $text, $openTags);
            preg_match_all("/<\/$tag>/i", $text, $closeTags);

            $openCount  = count($openTags[0]);
            $closeCount = count($closeTags[0]);

            if ($openCount > $closeCount) {
                $text = preg_replace("/<$tag\b[^>]*>/i", '', $text, $openCount - $closeCount);
            }
        }

        return $text;
    }
}
