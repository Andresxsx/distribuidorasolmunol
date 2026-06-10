<?php

namespace App\Filament\Resources\MovimientoBodegas\Pages;

use App\Filament\Resources\MovimientoBodegas\MovimientoBodegaResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMovimientoBodega extends ViewRecord
{
    protected static string $resource = MovimientoBodegaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
