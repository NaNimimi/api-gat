<?php

namespace App\Jobs;

use App\Telegram\TelegramBotApi;
use DB;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class SendTelegramLog implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly string $token,
        private readonly int $chatId,
        private readonly string $message
    ) {
        $this->onQueue('logging');

    }

    /**
     * @throws Exception
     */
    public function handle(): void
    {

        try {
            TelegramBotApi::sendMessage($this->token, $this->chatId, $this->message);

            // Логируем успешную отправку
            $this->logSuccess();
        } catch (Exception $e) {
            // Логируем ошибку
            $this->logError($e);

            throw $e;
        }
    }

    private function logSuccess(): void
    {
        DB::table('telegram_logs')->insert([
            'message_hash' => md5($this->message),
            'status' => 'sent',
            'message' => substr($this->message, 0, 255),
            'created_at' => now(),
        ]);
    }

    private function logError(Exception $e): void
    {
        Log::error("Failed to send message to Telegram: {$e->getMessage()}", [
            'exception' => $e,
            'message' => $this->message,
            'chat_id' => $this->chatId,
        ]);

        DB::table('telegram_logs')->insert([
            'message_hash' => md5($this->message),
            'status' => 'failed',
            'message' => $e->getMessage(),
            'created_at' => now(),
        ]);
    }

    public function failed(Exception $e): void
    {
        Log::error('Telegram log job failed', [
            'exception' => $e,
            'message' => $this->message,
            'chat_id' => $this->chatId,
        ]);
    }
}
