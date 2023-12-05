module.exports = `<?php

    namespace Bots\\TestBot\\Views;

    use Bots\\TestBot\\Views\\Keyboards\\Inline\\TestInlineKeyboard;
    use Bots\\TestBot\\Views\\Keyboards\\Reply\\TestReplyKeyboard;
    use Bots\\TestBot\\Views\\Messages\\TestMessage;
    use Lucifier\\Framework\\Core\\View\\View;

    class TestView extends View {
        public function configure(): void{
            $this->message = new TestMessage();

            // $this->keyboard = new TestInlineKeyboard();
            $this->keyboard = new TestInlineKeyboard();

            parent::configure();
        }
    }

?>`
