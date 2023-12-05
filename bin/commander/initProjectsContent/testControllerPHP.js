module.exports = `<?php

    namespace Bots\\TestBot\\Controllers;

    use Lucifier\\Framework\\Core\\Controller\\Controller;

    class TestController extends Controller {
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
