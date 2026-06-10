<?php

namespace App\Filament\Resources\MovimientoBodegas\Pages;

use App\Filament\Resources\MovimientoBodegas\MovimientoBodegaResource;
use Filament\Resources\Pages\ListRecords;

class ListMovimientoBodegas extends ListRecords
{
    protected static string $resource = MovimientoBodegaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}