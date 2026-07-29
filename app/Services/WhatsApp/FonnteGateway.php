<?php

namespace App\Services\WhatsApp;

use App\Contracts\WhatsAppGatewayInterface;
use App\Services\WhatsApp\Exceptions\WhatsAppGatewayException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fonnte (https://fonnte.com) WhatsApp gateway integration — a simple,
 * single-device WA API popular with Indonesian SMBs. Wablas or a
 * self-hosted Baileys bridge can replace this by implementing the same
 * WhatsAppGatewayInterface and rebinding it in WhatsAppServiceProvider.
 */
class FonnteGateway implements WhatsAppGatewayInterface
{
    public function __construct(private readonly string $token)
    {
    }

    public function sendText(string $phone, string $message): void
    {
        $this->send([
            'target' => $this->normalizePhone($phone),
            'message' => $message,
        ]);
    }

    public function sendImage(string $phone, string $imageUrl, string $caption = ''): void
    {
        $this->send([
            'target' => $this->normalizePhone($phone),
            'message' => $caption,
            'url' => $imageUrl,
        ]);
    }

    private function send(array $payload): void
    {
        $response = Http::withHeaders(['Authorization' => $this->token])
            ->asForm()
            ->post('https://api.fonnte.com/send', $payload);

        if ($response->failed() || $response->json('status') === false) {
            Log::error('Fonnte WhatsApp send failed', [
                'target' => $payload['target'],
                'response' => $response->json(),
            ]);

            throw new WhatsAppGatewayException(
                'Failed to send WhatsApp message: '.($response->json('reason') ?? 'Unknown gateway error.')
            );
        }
    }

    /**
     * Fonnte expects Indonesian numbers as 62xxxxxxxxxx — no leading 0 or +.
     */
    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }

        return $digits;
    }
}
