<?php

namespace App\Logging\Telegram;

use App\Jobs\SendTelegramLog;
use InvalidArgumentException;
use JsonException;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Monolog\LogRecord;
use Throwable;

final class TelegramLoggerHandler extends AbstractProcessingHandler
{
    private const TELEGRAM_MESSAGE_LIMIT = 4096;

    private const TRUNCATE_SUFFIX = ' [truncated]';

    private int $chatId;

    private string $token;

    public function __construct(array $config)
    {
        $this->validateConfig($config);
        $this->token = $config['token'];
        $this->chatId = $config['chat_id'];
        parent::__construct(Logger::toMonologLevel($config['level']));
    }

    protected function write(LogRecord $record): void
    {
        $message = $this->formatMessage($record);
        $this->truncateMessage($message);

        SendTelegramLog::dispatch($this->token, $this->chatId, $message);
    }

    /**
     * @throws JsonException
     */
    private function formatMessage(LogRecord $record): string
    {
        $header = sprintf(
            '[%s] %s.%s:',
            $record->datetime->format('Y-m-d H:i:s'),
            $record->channel,
            $record->level->name
        );

        $body = '';

        if ($record->message && $record->message !== '') {
            $body .= "\n\n".$record->message;
        }

        if(count($record['context'])) {
            $body .= "\n\n". json_encode($record['context'], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if ($exception = $this->extractException($record)) {
            $body .= "\n\n".$exception;
        }

        $environment = $record->context['environment'] ?? config('app.env');
        $body .= "\n\nEnvironment: ".$environment;

        return $header.$body;
    }

    private function extractException(LogRecord $record): ?string
    {
        $details = $record->context['exception'] ?? null;

        if ($details instanceof Throwable) {
            return (string) $details;
        }

        try {
            if (is_string($details) && json_decode($details, true, 512, JSON_THROW_ON_ERROR) !== null) {
                return json_encode(json_decode($details, true, 512, JSON_THROW_ON_ERROR), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: $details;
            }
        } catch (JsonException) {
            return $details;
        }

        if (is_array($details) || is_object($details)) {
            try {
                return json_encode($details, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: 'Unable to serialize exception details';
            } catch (JsonException) {
                return 'Unable to serialize exception details';
            }
        }

        return null;
    }

    private function truncateMessage(string &$message): void
    {
        if (strlen($message) > self::TELEGRAM_MESSAGE_LIMIT) {
            $message = substr($message, 0, self::TELEGRAM_MESSAGE_LIMIT - strlen(self::TRUNCATE_SUFFIX))
                .self::TRUNCATE_SUFFIX;
        }
    }

    private function validateConfig(array $config): void
    {
        $required = ['level', 'token', 'chat_id'];
        $missing = array_diff($required, array_keys($config));

        if ($missing) {
            throw new InvalidArgumentException('Missing required configuration keys: '.implode(', ', $missing));
        }

        if (! is_string($config['token'])) {
            throw new InvalidArgumentException('Token must be a string');
        }
    }
}
