module.exports = `<?php

    require __DIR__."/../vendor/autoload.php";

    ini_set("log_errors", 1);
    ini_set("error_log", __DIR__."/php-error.log");

    use Bots\\TestBot\\TestBot;
    use TelegramBot\\Api\\InvalidJsonException;

    try {
        $bot = new TestBot();

        $bot->setToken("5658818186:AAF1Sfgdeg-7ZclVUuFiTsoUCkItWW3rQAs");
        $bot->initClient();
        $bot->run();

    } catch(InvalidJsonException|Exception $err) {
        error_log($err);
    }

?>
`
