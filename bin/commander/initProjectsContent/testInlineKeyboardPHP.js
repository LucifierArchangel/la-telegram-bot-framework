module.exports = (botName, keyboardName) => `<?php

    namespace Bots\\${botName}\\Views\\Keyboards\\Inline;

    use Lucifier\\Framework\\Keyboard\\Inline\\InlineKeyboard;
    use Lucifier\\Framework\\Keyboard\\Keyboard;

    class ${keyboardName} extends Keyboard {
        public function configure(array $parameters = []): void {
            $this->type = "inline";

            $this->keyboard = new InlineKeyboard();

            $this->keyboard->addRow()->addButton("inline", "Text", "callback");
            $this->keyboard->addRow()->addButton("inline", "Text2", "callback2");

            parent::configure($parameters);
        }
    }

?>`
