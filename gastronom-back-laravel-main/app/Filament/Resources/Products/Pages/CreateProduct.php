<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    public function getTitle(): string
    {
        return __('enums.product.resource.pages.create.title');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label(__('enums.product.resource.pages.create.save')),
            $this->getCreateAnotherFormAction()
                ->label(__('enums.product.resource.pages.create.save_and_create_another')),
            $this->getCancelFormAction()
                ->label(__('enums.product.resource.pages.create.cancel')),
        ];
    }
}
