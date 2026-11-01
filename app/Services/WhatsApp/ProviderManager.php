<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Contracts\WhatsAppProvider;
use App\Enums\WhatsAppDriver;
use App\Services\WhatsApp\Drivers\EvolutionProvider;
use App\Services\WhatsApp\Drivers\MetaProvider;
use App\Services\WhatsApp\Drivers\TwilioProvider;

class ProviderManager
{
    public function for(WhatsAppDriver $driver): WhatsAppProvider
    {
        return match ($driver) {
            WhatsAppDriver::Meta => app(MetaProvider::class),
            WhatsAppDriver::Evolution => app(EvolutionProvider::class),
            WhatsAppDriver::Twilio => app(TwilioProvider::class),
        };
    }
}
