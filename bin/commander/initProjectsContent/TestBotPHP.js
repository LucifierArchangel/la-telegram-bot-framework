module.exports = `<?php

    namespace Bots\\TestBot;

    use Lucifier\\Framework\\Core\\Bot\\Bot;
    use Lucifier\\Framework\\Core\\BotRouter\\BotRouter;

    class TestBot extends Bot {
        public function initClient(): void {
            $this->router = new BotRouter();

            $this->router->setNamespace(__NAMESPACE__);

            $this->router->command("start", "TestController@testHandler");

            parent::initClient();
        }
    }

?>
`
