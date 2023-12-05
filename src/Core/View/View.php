<?php

    namespace Lucifier\Framework\Core\View;

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
            $this->configure();


            $text = $this->message->run($message ?? []);
            $answerKeyboard = null;

            if (isset($this->keyboard)) {
                $answerKeyboard = $this->keyboard->build($keyboard ?? []);

                $answerKeyboard = $this->keyboard->getType() === "inline" ?
                    new InlineKeyboardMarkup($answerKeyboard) :
                    new ReplyKeyboardMarkup($answerKeyboard, false, true);
            }

            $message = $this->update->getMessage();
            $chatId = $message->getChat()->getId();

            if ($this->message->getType() === "send") {
                $this->bot->sendMessage($chatId, $text, "HTML", $this->message->getPreview(), null, $answerKeyboard);
            }
        }
    }

?>