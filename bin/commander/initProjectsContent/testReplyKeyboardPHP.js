module.exports = (botName, keyboardName) => `<?php

    namespace Bots\\${botName}\\Views\\Keyboards\\Reply;

    use Lucifier\\Framework\\Keyboard\\Keyboard;
    use Lucifier\\Framework\\Keyboard\\Reply\\ReplyKeyboard;

    class ${keyboardName} extends Keyboard {
        public function  configure(array $parameters = []): void {
            $this->keyboard = new ReplyKeyboard();
            $this->keyboard->addRow()->addButton("Text")->addButton("Text 2");
            $this->keyboard->addRow()->addButton("Text 3");

            parent::configure($parameters);
        }
    }

?>`
