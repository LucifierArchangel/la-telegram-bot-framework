module.exports = (botName, viewName) => `<?php

    namespace Bots\\${botName}\\Views;

    use Bots\\${botName}\\Views\\Keyboards\\Inline\\TestInlineKeyboard;
    use Bots\\${botName}\\Views\\Keyboards\\Reply\\TestReplyKeyboard;
    use Bots\\${botName}\\Views\\Messages\\TestMessage;
    use Lucifier\\Framework\\Core\\View\\View;

    class ${viewName} extends View {
        public function configure(): void{
            $this->message = new TestMessage();

            // $this->keyboard = new TestInlineKeyboard();
            $this->keyboard = new TestInlineKeyboard();

            parent::configure();
        }
    }

?>`
