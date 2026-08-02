<?php

namespace App\Filament\Resources\ProductSales\Pages;

use App\Filament\Resources\ProductSales\ProductSaleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProductSale extends EditRecord
{
    protected static string $resource = ProductSaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
