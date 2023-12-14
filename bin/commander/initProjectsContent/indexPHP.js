module.exports = `<?php

    require __DIR__."/../vendor/autoload.php";

    ini_set("log_errors", 1);
    ini_set("error_log", __DIR__."/php-error.log");

    use Bots\\TestBot\\TestBot;
    use TelegramBot\\Api\\InvalidJsonException;

    try {
        $bot = new TestBot();

        $bot->setToken("<YOUR_TOKEN>");
        $bot->initClient();
        $bot->run();

    } catch(InvalidJsonException|Exception $err) {
        error_log($err);
    }

?>
`
