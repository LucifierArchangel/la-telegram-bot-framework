module.exports = (botName, messageName) => `<?php

    namespace Bots\\${botName}\\Views\\Messages;

    use Lucifier\\Framework\\Message\\Message;

    class ${messageName} extends Message {
        protected $fields = ["name"];
        protected $template = "Hello {{ name }}";
    }

?>`
