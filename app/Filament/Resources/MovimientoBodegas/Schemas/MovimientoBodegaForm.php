<?php

namespace App\Filament\Resources\MovimientoBodegas\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MovimientoBodegaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('codigo_movimiento'),
                TextInput::make('producto_id')
                    ->required()
                    ->numeric(),
                TextInput::make('tipo_movimiento')
                    ->required(),
                TextInput::make('origen')
                    ->required(),
                TextInput::make('documento_referencia'),
                TextInput::make('cantidad')
                    ->required()
                    ->numeric(),
                TextInput::make('stock_anterior')
                    ->required()
                    ->numeric(),
                TextInput::make('stock_nuevo')
                    ->required()
                    ->numeric(),
                TextInput::make('user_id')
                    ->numeric(),
                DateTimePicker::make('fecha')
                    ->required(),
                TextInput::make('observacion'),
            ]);
    }
}
