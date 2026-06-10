<?php

namespace App\Filament\Resources\MovimientoBodegas;

use App\Filament\Resources\MovimientoBodegas\Pages\ListMovimientoBodegas;
use App\Filament\Resources\MovimientoBodegas\Pages\ViewMovimientoBodega;
use App\Filament\Resources\MovimientoBodegas\Schemas\MovimientoBodegaInfolist;
use App\Filament\Resources\MovimientoBodegas\Tables\MovimientoBodegasTable;
use App\Models\MovimientoBodega;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class MovimientoBodegaResource extends Resource
{
    protected static ?string $model = MovimientoBodega::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $recordTitleAttribute = 'codigo_movimiento';

    protected static ?string $navigationLabel = 'Movimientos de Bodega';

    protected static ?string $modelLabel = 'Movimiento de Bodega';

    protected static ?string $pluralModelLabel = 'Movimientos de Bodega';

    protected static ?string $slug = 'movimientos-bodega';

protected static string|\UnitEnum|null $navigationGroup = 'Bodega, Compras y Ventas';
    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MovimientoBodegaInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MovimientoBodegasTable::configure($table);
    }

    public static function canCreate(): bool
{
    return false;
}

public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
{
    return false;
}

public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
{
    return false;
}

public static function canDeleteAny(): bool
{
    return false;
}

    public static function getPages(): array
    {
        return [
            'index' => ListMovimientoBodegas::route('/'),
            'view' => ViewMovimientoBodega::route('/{record}'),
        ];
    }
}