<?php

namespace App\Filament\Resources\DeliveryMethods\DeliveryMethodResource\Pages;

use App\Filament\Resources\DeliveryMethods\DeliveryMethodResource;
use Filament\Resources\Pages\ListRecords;

class ListDeliveryMethods extends ListRecords
{
    protected static string $resource = DeliveryMethodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
