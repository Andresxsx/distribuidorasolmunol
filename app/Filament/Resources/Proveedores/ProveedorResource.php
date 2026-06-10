<?php

namespace App\Filament\Resources\Proveedores;
use App\Filament\Resources\Concerns\ControlaPermisosPorRol;
use App\Filament\Resources\Proveedores\Pages\CreateProveedor;
use App\Filament\Resources\Proveedores\Pages\EditProveedor;
use App\Filament\Resources\Proveedores\Pages\ListProveedores;
use App\Filament\Resources\Proveedores\Pages\ViewProveedor;
use App\Filament\Resources\Proveedores\Schemas\ProveedorInfolist;
use App\Filament\Resources\Proveedores\Tables\ProveedoresTable;
use App\Models\Proveedor;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ProveedorResource extends Resource
{
    use ControlaPermisosPorRol;
    protected static ?string $model = Proveedor::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static ?string $recordTitleAttribute = 'nombre';

    protected static ?string $navigationLabel = 'Proveedores';

    protected static ?string $modelLabel = 'Proveedor';

    protected static ?string $pluralModelLabel = 'Proveedores';

    protected static ?string $slug = 'proveedores';

protected static string|\UnitEnum|null $navigationGroup = 'Bodega, Compras y Ventas';
    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('ruc')
                    ->label('RUC')
                    ->required()
                    ->minLength(13)
                    ->maxLength(13)
                    ->inputMode('numeric')
                    ->extraInputAttributes([
                        'maxlength' => 13,
                        'oninput' => "this.value = this.value.replace(/[^0-9]/g, '').slice(0, 13)",
                    ])
                    ->rules([
                        'required',
                        'digits:13',
                        function () {
                            return function (string $attribute, $value, \Closure $fail): void {
                                $ruc = preg_replace('/\D/', '', (string) $value);

                                if (! self::validarRucEcuador($ruc)) {
                                    $fail('El RUC ingresado no es válido para Ecuador. Debe ser una cédula válida + 001, sociedad privada o entidad pública.');
                                }
                            };
                        },
                    ])
                    ->unique(table: 'proveedores', column: 'ruc', ignoreRecord: true)
                    ->helperText('Debe tener 13 dígitos. Para persona natural: cédula válida + 001.'),

                TextInput::make('nombre')
                    ->label('Nombre / Razón social')
                    ->required()
                    ->minLength(3)
                    ->maxLength(150)
                    ->extraInputAttributes([
                        'maxlength' => 150,
                    ])
                    ->rules([
                        'required',
                        'regex:/^[\pL\pN\s.,&\/()\-]+$/u',
                    ])
                    ->helperText('Máximo 150 caracteres. Puede incluir letras, números, espacios, punto, coma, guion, &, / y paréntesis.'),

                TextInput::make('telefono')
                    ->label('Teléfono de contacto')
                    ->required()
                    ->minLength(9)
                    ->maxLength(10)
                    ->inputMode('numeric')
                    ->extraInputAttributes([
                        'maxlength' => 10,
                        'oninput' => "this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10)",
                    ])
                    ->rules([
                        'required',
                        'regex:/^(09[0-9]{8}|0[2-7][0-9]{7})$/',
                    ])
                    ->helperText('Celular: 09XXXXXXXX. Convencional: 02XXXXXXX, 04XXXXXXX, etc.'),

                TextInput::make('correo')
                    ->label('Correo electrónico')
                    ->required()
                    ->email()
                    ->maxLength(100)
                    ->extraInputAttributes([
                        'maxlength' => 100,
                    ])
                    ->helperText('Ejemplo: proveedor@gmail.com'),

                TextInput::make('direccion')
                    ->label('Dirección')
                    ->required()
                    ->minLength(5)
                    ->maxLength(180)
                    ->extraInputAttributes([
                        'maxlength' => 180,
                    ])
                    ->helperText('Ejemplo: Av. Principal y Calle 10, Baba'),

                Select::make('estado')
                    ->label('Estado')
                    ->options([
                        'Activo' => 'Activo',
                        'Inactivo' => 'Inactivo',
                    ])
                    ->default('Activo')
                    ->required(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProveedorInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProveedoresTable::configure($table);
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
            'index' => ListProveedores::route('/'),
            'create' => CreateProveedor::route('/create'),
            'view' => ViewProveedor::route('/{record}'),
            'edit' => EditProveedor::route('/{record}/edit'),
        ];
    }

    private static function validarRucEcuador(string $ruc): bool
    {
        if (! preg_match('/^[0-9]{13}$/', $ruc)) {
            return false;
        }

        if (preg_match('/^(\d)\1{12}$/', $ruc)) {
            return false;
        }

        $provincia = intval(substr($ruc, 0, 2));
        $tercerDigito = intval($ruc[2]);

        if ($provincia < 1 || $provincia > 24) {
            return false;
        }

        if ($tercerDigito >= 0 && $tercerDigito <= 5) {
            $cedula = substr($ruc, 0, 10);
            $establecimiento = substr($ruc, 10, 3);

            return self::validarCedulaEcuador($cedula) && $establecimiento === '001';
        }

        if ($tercerDigito === 6) {
            return self::validarRucPublico($ruc);
        }

        if ($tercerDigito === 9) {
            return self::validarRucPrivado($ruc);
        }

        return false;
    }

    private static function validarCedulaEcuador(string $cedula): bool
    {
        if (! preg_match('/^[0-9]{10}$/', $cedula)) {
            return false;
        }

        if (preg_match('/^(\d)\1{9}$/', $cedula)) {
            return false;
        }

        $provincia = intval(substr($cedula, 0, 2));
        $tercerDigito = intval($cedula[2]);

        if ($provincia < 1 || $provincia > 24) {
            return false;
        }

        if ($tercerDigito > 5) {
            return false;
        }

        $coeficientes = [2, 1, 2, 1, 2, 1, 2, 1, 2];
        $suma = 0;

        for ($i = 0; $i < 9; $i++) {
            $valor = intval($cedula[$i]) * $coeficientes[$i];

            if ($valor >= 10) {
                $valor -= 9;
            }

            $suma += $valor;
        }

        $digitoCalculado = (10 - ($suma % 10)) % 10;
        $digitoReal = intval($cedula[9]);

        return $digitoCalculado === $digitoReal;
    }

    private static function validarRucPrivado(string $ruc): bool
    {
        $establecimiento = substr($ruc, 10, 3);

        if (intval($establecimiento) <= 0) {
            return false;
        }

        $coeficientes = [4, 3, 2, 7, 6, 5, 4, 3, 2];
        $suma = 0;

        for ($i = 0; $i < 9; $i++) {
            $suma += intval($ruc[$i]) * $coeficientes[$i];
        }

        $residuo = $suma % 11;
        $digitoCalculado = 11 - $residuo;

        if ($digitoCalculado === 11) {
            $digitoCalculado = 0;
        }

        if ($digitoCalculado === 10) {
            return false;
        }

        return $digitoCalculado === intval($ruc[9]);
    }

    private static function validarRucPublico(string $ruc): bool
    {
        $establecimiento = substr($ruc, 9, 4);

        if (intval($establecimiento) <= 0) {
            return false;
        }

        $coeficientes = [3, 2, 7, 6, 5, 4, 3, 2];
        $suma = 0;

        for ($i = 0; $i < 8; $i++) {
            $suma += intval($ruc[$i]) * $coeficientes[$i];
        }

        $residuo = $suma % 11;
        $digitoCalculado = 11 - $residuo;

        if ($digitoCalculado === 11) {
            $digitoCalculado = 0;
        }

        if ($digitoCalculado === 10) {
            return false;
        }

        return $digitoCalculado === intval($ruc[8]);
    }
}