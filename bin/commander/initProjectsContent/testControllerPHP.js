module.exports = (botName, controllerName) => `<?php

    namespace Bots\\${botName}\\Controllers;

    use Lucifier\\Framework\\Core\\Controller\\Controller;

    class ${controllerName} extends Controller {
        public function testHandler($update, $bot) {
            $this->view("TestView", [
                "update" => $update,
                "bot" => $bot,
                "message" => [
                    "name" => "UserName"
                ],
                "keyboard" => [
                    "name" => "UserName"
                ]
            ]);
        }
    }

?>`
