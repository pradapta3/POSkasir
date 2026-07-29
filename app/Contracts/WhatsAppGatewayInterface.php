<?php

namespace App\Contracts;

/**
 * Abstracts the WhatsApp gateway (Fonnte by default) so it can be swapped
 * for Wablas or a self-hosted Baileys bridge via the WhatsAppServiceProvider
 * binding alone — SendWhatsAppInvoiceJob depends only on this contract.
 */
interface WhatsAppGatewayInterface
{
    public function sendText(string $phone, string $message): void;

    public function sendImage(string $phone, string $imageUrl, string $caption = ''): void;
}
