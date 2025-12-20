<?php

namespace App\Filament\Resources\PerfilResource\Pages;

use App\Filament\Resources\PerfilResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPerfils extends ListRecords
{
    protected static string $resource = PerfilResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
