<?php

namespace App\Logging\Telegram;

use Monolog\Logger;

final class TelegramLoggerFactory
{
    public function __invoke(array $config): Logger
    {
        $name = $config['name'] ?? 'telegram';
        $logger = new Logger($name);
        $logger->pushHandler(new TelegramLoggerHandler($config));

        return $logger;
    }
}
