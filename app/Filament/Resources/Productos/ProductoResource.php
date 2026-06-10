<?php

namespace App\Filament\Resources\Productos;
use App\Filament\Resources\Concerns\ControlaPermisosPorRol;
use App\Filament\Resources\Productos\Pages\CreateProducto;
use App\Filament\Resources\Productos\Pages\EditProducto;
use App\Filament\Resources\Productos\Pages\ListProductos;
use App\Filament\Resources\Productos\Pages\ViewProducto;
use App\Filament\Resources\Productos\Schemas\ProductoInfolist;
use App\Filament\Resources\Productos\Tables\ProductosTable;
use App\Models\Producto;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ProductoResource extends Resource
{
    use ControlaPermisosPorRol;

    protected static ?string $model = Producto::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static ?string $recordTitleAttribute = 'nombre';

    protected static ?string $navigationLabel = 'Productos';

    protected static ?string $modelLabel = 'Producto';

    protected static ?string $pluralModelLabel = 'Productos';

    protected static ?string $slug = 'productos';

protected static string|\UnitEnum|null $navigationGroup = 'Bodega, Compras y Ventas';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('codigo')
                    ->label('Código')
                    ->disabled()
                    ->dehydrated(false)
                    ->placeholder('Se generará automáticamente')
                    ->helperText('El sistema genera el código del producto.'),

                TextInput::make('nombre')
                    ->label('Nombre del producto')
                    ->required()
                    ->minLength(2)
                    ->maxLength(100)
                    ->extraInputAttributes([
                        'maxlength' => 100,
                    ])
                    ->rules([
                        'required',
                        'regex:/^[\pL\pN\s.,&\/()\-]+$/u',
                    ])
                    ->helperText('Ejemplo: Sal refinada, Arroz flor, Aceite vegetal.'),

                Select::make('categoria')
                    ->label('Categoría')
                    ->required()
                    ->options([
                        'Víveres y abarrotes' => 'Víveres y abarrotes',
                        'Granos y cereales' => 'Granos y cereales',
                        'Bebidas' => 'Bebidas',
                        'Limpieza' => 'Limpieza',
                        'Tecnología' => 'Tecnología',
                        'Oficina' => 'Oficina',
                        'Ferretería' => 'Ferretería',
                        'Farmacia' => 'Farmacia',
                        'Otros' => 'Otros',
                    ])
                    ->searchable()
                    ->helperText('Seleccione una categoría válida.'),

                Textarea::make('descripcion')
                    ->label('Descripción')
                    ->maxLength(250)
                    ->extraInputAttributes([
                        'maxlength' => 250,
                    ])
                    ->helperText('Descripción opcional del producto.'),

                TextInput::make('stock_actual')
                    ->label('Stock actual')
                    ->default(0)
                    ->disabled()
                    ->dehydrated(false)
                    ->numeric()
                    ->helperText('El stock actual se calcula con compras y ventas.'),

                TextInput::make('stock_minimo')
                    ->label('Stock mínimo')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(999999)
                    ->helperText('Cantidad mínima antes de recomendar nueva compra.'),

                TextInput::make('precio_compra')
                    ->label('Precio de compra referencial')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->prefix('$')
                    ->helperText('Costo referencial del producto.'),

                TextInput::make('precio_venta')
                    ->label('Precio de venta')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->prefix('$')
                    ->helperText('Debe ser mayor o igual al precio de compra.'),

                Select::make('estado')
                    ->label('Estado')
                    ->required()
                    ->options([
                        'Activo' => 'Activo',
                        'Inactivo' => 'Inactivo',
                    ])
                    ->default('Activo'),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProductoInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductosTable::configure($table);
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
            'index' => ListProductos::route('/'),
            'create' => CreateProducto::route('/create'),
            'view' => ViewProducto::route('/{record}'),
            'edit' => EditProducto::route('/{record}/edit'),
        ];
    }
}