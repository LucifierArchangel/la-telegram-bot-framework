module.exports = `<?php

    namespace Bots\\TestBot\\Views\\Messages;

    use Lucifier\\Framework\\Message\\Message;

    class TestMessage extends Message {
        protected $fields = ["name"];
        protected $template = "Hello {{ name }}";
    }

?>`
