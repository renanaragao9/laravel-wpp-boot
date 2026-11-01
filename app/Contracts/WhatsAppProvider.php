<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Enums\WhatsAppDriver;
use App\Support\WhatsApp\SendResult;
use Illuminate\Http\Request;

interface WhatsAppProvider
{
    public function driver(): WhatsAppDriver;

    public function authorize(Request $request): bool;

    public function parse(Request $request): array;

    public function sendText(string $phone, string $text): SendResult;
}
