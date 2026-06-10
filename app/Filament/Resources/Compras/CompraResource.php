<?php

namespace App\Filament\Resources\Compras;
use App\Filament\Resources\Concerns\ControlaPermisosPorRol;
use App\Filament\Resources\Compras\Pages\CreateCompra;
use App\Filament\Resources\Compras\Pages\EditCompra;
use App\Filament\Resources\Compras\Pages\ListCompras;
use App\Filament\Resources\Compras\Pages\ViewCompra;
use App\Filament\Resources\Compras\Schemas\CompraInfolist;
use App\Filament\Resources\Compras\Tables\ComprasTable;
use App\Models\Compra;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class CompraResource extends Resource
{
    use ControlaPermisosPorRol;
    protected static ?string $model = Compra::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $recordTitleAttribute = 'numero_compra';

    protected static ?string $navigationLabel = 'Compras';

    protected static ?string $modelLabel = 'Compra';

    protected static ?string $pluralModelLabel = 'Compras';

    protected static ?string $slug = 'compras';

protected static string|\UnitEnum|null $navigationGroup = 'Bodega, Compras y Ventas';
    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('numero_compra')
                    ->label('Número de compra')
                    ->disabled()
                    ->dehydrated(false)
                    ->placeholder('Se generará automáticamente')
                    ->helperText('El sistema genera el número de compra.'),

                DatePicker::make('fecha')
                    ->label('Fecha de compra')
                    ->default(now())
                    ->maxDate(now())
                    ->required()
                    ->helperText('No se permiten fechas futuras.'),

                Select::make('proveedor_id')
                    ->label('Proveedor')
                    ->relationship(
                        name: 'proveedor',
                        titleAttribute: 'nombre',
                        modifyQueryUsing: fn ($query) => $query->where('estado', 'Activo')
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->helperText('Solo se muestran proveedores activos.'),

                Select::make('producto_id')
                    ->label('Producto')
                    ->relationship(
                        name: 'producto',
                        titleAttribute: 'nombre',
                        modifyQueryUsing: fn ($query) => $query->where('estado', 'Activo')
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->helperText('Solo se muestran productos activos.'),

                TextInput::make('cantidad')
                    ->label('Cantidad comprada')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(999999)
                    ->extraInputAttributes([
                        'min' => 1,
                        'step' => 1,
                    ])
                    ->helperText('Ingrese la cantidad de productos comprados.'),

                TextInput::make('precio_unitario')
                    ->label('Precio unitario de compra')
                    ->required()
                    ->numeric()
                    ->minValue(0.01)
                    ->prefix('$')
                    ->extraInputAttributes([
                        'min' => 0.01,
                        'step' => '0.01',
                    ])
                    ->helperText('Precio real pagado al proveedor.'),

                TextInput::make('total')
                    ->label('Total')
                    ->disabled()
                    ->dehydrated(false)
                    ->prefix('$')
                    ->helperText('Se calcula automáticamente al guardar.'),

                Textarea::make('observacion')
                    ->label('Observación')
                    ->maxLength(200)
                    ->extraInputAttributes([
                        'maxlength' => 200,
                    ])
                    ->helperText('Opcional. Ejemplo: factura, lote o detalle de compra.'),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CompraInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ComprasTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCompras::route('/'),
            'create' => CreateCompra::route('/create'),
            'view' => ViewCompra::route('/{record}'),
            'edit' => EditCompra::route('/{record}/edit'),
        ];
    }
}