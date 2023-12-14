module.exports = `<?php

    namespace Bots\\TestBot;

    use Lucifier\\Framework\\Core\\Bot\\Bot;
    use Lucifier\\Framework\\Core\\BotRouter\\BotRouter;
    use Bots\\TestBot\\Controllers\\TestController;

    class TestBot extends Bot {
        public function initClient(): void {
            $this->router = new BotRouter();

            $this->router->command("start", [TestController::class, "testHandler"]);

            parent::initClient();
        }
    }

?>
`
