<?php

namespace App\Filament\Resources\Demands\Pages;

use App\Filament\Resources\Demands\WhatsAppDemandResource;
use Filament\Resources\Pages\ListRecords;

class ListDemands extends ListRecords
{
    protected static string $resource = WhatsAppDemandResource::class;
}
