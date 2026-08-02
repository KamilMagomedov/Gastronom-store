<?php

namespace App\Filament\Resources\Supports\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SupportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('order_id')
                    ->numeric()
                    ->nullable()
                    ->label(__('enums.resource.form.order_id')),
                Select::make('theme')
                    ->required()
                    ->options(fn () => collect(\App\Enums\SupportTheme::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()]))
                    ->enum(\App\Enums\SupportTheme::class)
                    ->label(__('enums.resource.form.theme')),
                Textarea::make('message')
                    ->required()
                    ->columnSpanFull()
                    ->label(__('enums.resource.form.message')),
                Select::make('status')
                    ->required()
                    ->options(fn () => collect(\App\Enums\SupportStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()]))
                    ->enum(\App\Enums\SupportStatus::class)
                    ->default(\App\Enums\SupportStatus::NEW)
                    ->label(__('enums.resource.form.status')),
            ]);
    }
}
