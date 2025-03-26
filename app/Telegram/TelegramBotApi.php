<?php

namespace App\Telegram;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class TelegramBotApi
{
    private const HOST = 'https://api.telegram.org/bot';

    public static function sendMessage(string $token, int $chatId, string $text): void
    {
        try {
            $response = Http::get(self::HOST.$token.'/sendMessage', [
                'chat_id' => $chatId,
                'text' => sprintf('```PHP
%s```', $text),
                'parse_mode' => 'MarkdownV2',
            ]);

            // Проверка успешности обработки запроса
            if (! $response->successful()) {
                Log::error('Не удалось отправить сообщение в Telegram: '.$response->body());
                throw new RuntimeException('Не удалось отправить сообщение в Telegram: '.$response->body());
            }
        } catch (Exception $e) {
            // Логируем ошибку или обрабатываем её по необходимости
            Log::error("Ошибка при отправке сообщения в Telegram: {$e->getMessage()}", [
                'exception' => $e,
                'token' => $token,
                'chat_id' => $chatId,
                'text' => $text,
            ]);
        }
    }
}
