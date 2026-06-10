<?php

namespace App\Filament\Resources\Ventas;
use App\Filament\Resources\Concerns\ControlaPermisosPorRol;
use App\Filament\Resources\Ventas\Pages\CreateVenta;
use App\Filament\Resources\Ventas\Pages\EditVenta;
use App\Filament\Resources\Ventas\Pages\ListVentas;
use App\Filament\Resources\Ventas\Pages\ViewVenta;
use App\Filament\Resources\Ventas\Schemas\VentaInfolist;
use App\Filament\Resources\Ventas\Tables\VentasTable;
use App\Models\Producto;
use App\Models\Venta;
use BackedEnum;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class VentaResource extends Resource
{
    use ControlaPermisosPorRol;
    protected static ?string $model = Venta::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $recordTitleAttribute = 'numero_venta';

    protected static ?string $navigationLabel = 'Ventas';

    protected static ?string $modelLabel = 'Venta';

    protected static ?string $pluralModelLabel = 'Ventas';

    protected static ?string $slug = 'ventas';

protected static string|\UnitEnum|null $navigationGroup = 'Bodega, Compras y Ventas';
    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('numero_venta')
                    ->label('Número de venta')
                    ->disabled()
                    ->dehydrated(false)
                    ->placeholder('Se generará automáticamente')
                    ->helperText('El sistema genera el número de venta.'),

                DatePicker::make('fecha')
                    ->label('Fecha de venta')
                    ->default(now())
                    ->maxDate(now())
                    ->required()
                    ->helperText('No se permiten fechas futuras.'),

                Select::make('cliente_id')
                    ->label('Cliente')
                    ->relationship(
                        name: 'cliente',
                        titleAttribute: 'nombre',
                        modifyQueryUsing: fn ($query) => $query->where('estado', 'Activo')
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->helperText('Solo se muestran clientes activos.'),

                Select::make('producto_id')
                    ->label('Producto')
                    ->relationship(
                        name: 'producto',
                        titleAttribute: 'nombre',
                        modifyQueryUsing: fn ($query) => $query->where('estado', 'Activo')
                    )
                    ->searchable()
                    ->preload()
                    ->live()
                    ->required()
                    ->helperText(function (Get $get): string {
                        $producto = Producto::find($get('producto_id'));

                        if (! $producto) {
                            return 'Seleccione un producto activo.';
                        }

                        return 'Stock disponible actual: ' . $producto->stock_actual . ' unidad(es).';
                    }),

                TextInput::make('cantidad')
                    ->label('Cantidad vendida')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(999999)
                    ->live(onBlur: true)
                    ->extraInputAttributes([
                        'min' => 1,
                        'step' => 1,
                    ])
                    ->rules([
                        function (Get $get): Closure {
                            return function (string $attribute, $value, Closure $fail) use ($get): void {
                                $productoId = $get('producto_id');

                                if (! $productoId) {
                                    $fail('Primero debe seleccionar un producto.');
                                    return;
                                }

                                $producto = Producto::find($productoId);

                                if (! $producto) {
                                    $fail('El producto seleccionado no existe.');
                                    return;
                                }

                                $cantidad = (int) $value;
                                $stockDisponible = (int) $producto->stock_actual;

                                if ($cantidad <= 0) {
                                    $fail('La cantidad vendida debe ser mayor a cero.');
                                    return;
                                }

                                if ($cantidad > $stockDisponible) {
                                    $fail(
                                        'No hay stock suficiente. Disponible: ' .
                                        $stockDisponible .
                                        ' unidad(es). Intentó vender: ' .
                                        $cantidad .
                                        '.'
                                    );
                                }
                            };
                        },
                    ])
                    ->helperText(function (Get $get): string {
                        $producto = Producto::find($get('producto_id'));

                        if (! $producto) {
                            return 'Seleccione un producto antes de ingresar la cantidad.';
                        }

                        return 'No puede vender más de ' . $producto->stock_actual . ' unidad(es).';
                    }),

                TextInput::make('precio_unitario')
                    ->label('Precio unitario de venta')
                    ->required()
                    ->numeric()
                    ->minValue(0.01)
                    ->prefix('$')
                    ->extraInputAttributes([
                        'min' => 0.01,
                        'step' => '0.01',
                    ])
                    ->helperText('Precio real de venta al cliente.'),

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
                    ->helperText('Opcional. Ejemplo: venta al contado, factura o detalle de entrega.'),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return VentaInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VentasTable::configure($table);
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
            'index' => ListVentas::route('/'),
            'create' => CreateVenta::route('/create'),
            'view' => ViewVenta::route('/{record}'),
            'edit' => EditVenta::route('/{record}/edit'),
        ];
    }
}