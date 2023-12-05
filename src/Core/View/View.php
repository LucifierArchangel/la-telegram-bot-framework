<?php

    namespace Lucifier\Framework\Core\View;

    use Lucifier\Framework\Utils\Logger\FileLogger;
    use TelegramBot\Api\Client;
    use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
    use TelegramBot\Api\Types\ReplyKeyboardMarkup;
    use TelegramBot\Api\Types\Update;

    class View {
        protected $message;
        protected $keyboard;

        protected Client $bot;
        protected Update $update;

        public function __construct($update, $bot) {
            $this->update = $update;
            $this->bot = $bot;
        }

        public function configure() {}

        public function show($message=[], $keyboard=[]): void {
            $currentMessage = $this->update->getMessage();


            $chatId = null;

            if(isset($currentMessage)) {
                $chatId = $currentMessage->getChat()->getId();
            } else {
                $currentMessage = $this->update->getCallbackQuery();

                if (isset($currentMessage)) {
                    $chatId = $currentMessage->getMessage()->getChat()->getId();
                }
            }

            if (isset($chatId)) {
                $this->configure();

                $text = $this->message->run($message ?? []);
                $answerKeyboard = null;

                if (isset($this->keyboard)) {
                    $answerKeyboard = $this->keyboard->build($keyboard ?? []);

                    $answerKeyboard = $this->keyboard->getType() === "inline" ?
                        new InlineKeyboardMarkup($answerKeyboard) :
                        new ReplyKeyboardMarkup($answerKeyboard, false, true);
                }

                if ($this->message->getType() === "send") {
                    $this->bot->sendMessage($chatId, $text, "HTML", $this->message->getPreview(), null, $answerKeyboard);
                }
            }
        }
    }

?>