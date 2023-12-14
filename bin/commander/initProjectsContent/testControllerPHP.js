module.exports = (botName, controllerName) => `<?php

    namespace Bots\\${botName}\\Controllers;

    use Lucifier\\Framework\\Core\\Controller\\Controller;
    use Bots\\${botName}\\Views\\TestView;

    class ${controllerName} extends Controller {
        public function testHandler($update, $bot) {
            $this->view(TestView::class, [
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
