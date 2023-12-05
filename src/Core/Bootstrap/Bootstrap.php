<?php

    namespace Lucifier\Framework\Core\Bootstrap;

    use Lucifier\Framework\Core\Application\Application;
    use Lucifier\Framework\Core\Bot\Bot;
    use TelegramBot\Api\InvalidJsonException;

    class Bootstrap {

        private static function getBotString(string $uri): string {
            $params = explode("?", $uri);

            return $params[0];
        }

        private static function getPrefix(string $botParams): string {
            $params = explode("/", $botParams);

            return $params[1];
        }

        private static function getBotIdent(string $botParams): string {
            $params = explode("/", $botParams);

            return $params[1];
        }

        private static function getBot(string $prefix, Application $application): Bot|null {
            return $application->getBotByPrefix($prefix);
        }

        /**
         * @throws InvalidJsonException
         */
        public static function Bootstrap(Application $application): void {
            $serverUri = $_SERVER['REQUEST_URI'];
            $botParams = self::getBotString($serverUri);

            $prefix = self::getPrefix($botParams);
            $botIdent = self::getBotIdent($botParams);

            $bot = self::getBot($prefix, $application);

            if (is_null($bot)) {
                echo "Can not find bot";
            } else {
                $bot->initClient();
                $bot->run();
            }
        }
    }

?>