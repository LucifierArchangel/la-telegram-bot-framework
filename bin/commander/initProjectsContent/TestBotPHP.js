module.exports = (botName) => `<?php

    namespace Bots\\${botName};

    use Lucifier\\Framework\\Core\\Bot\\Bot;
    use Lucifier\\Framework\\Core\\BotRouter\\BotRouter;
    use Bots\\${botName}\\Controllers\\TestController;

    class ${botName} extends Bot {
        public function initClient(): void {
            $this->router = new BotRouter();

            $this->router->command("start", [TestController::class, "testHandler"]);

            parent::initClient();
        }
    }

?>
`
