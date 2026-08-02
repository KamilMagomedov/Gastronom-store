<?php

namespace App\Filament\Resources\ProductAttributes\Pages;

use App\Filament\Resources\ProductAttributes\ProductAttributeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProductAttribute extends CreateRecord
{
    protected static string $resource = ProductAttributeResource::class;

    public function getTitle(): string
    {
        return 'Создание атрибута товара';
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Создать'),
            $this->getCreateAnotherFormAction()
                ->label('Создать и добавить еще'),
            $this->getCancelFormAction()
                ->label('Отмена'),
        ];
    }
}
