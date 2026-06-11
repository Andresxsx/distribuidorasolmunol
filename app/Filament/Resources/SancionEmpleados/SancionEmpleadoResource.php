<?php

namespace App\Filament\Resources\SancionEmpleados;

use App\Filament\Resources\Concerns\ControlaPermisosPorRol;
use App\Filament\Resources\SancionEmpleados\Pages\CreateSancionEmpleado;
use App\Filament\Resources\SancionEmpleados\Pages\EditSancionEmpleado;
use App\Filament\Resources\SancionEmpleados\Pages\ListSancionEmpleados;
use App\Filament\Resources\SancionEmpleados\Pages\ViewSancionEmpleado;
use App\Models\Empleado;
use App\Models\SancionEmpleado;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SancionEmpleadoResource extends Resource
{
    use ControlaPermisosPorRol;

    protected static ?string $model = SancionEmpleado::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $recordTitleAttribute = 'motivo';

    protected static ?string $navigationLabel = 'Sanciones';

    protected static ?string $modelLabel = 'Sanción';

    protected static ?string $pluralModelLabel = 'Sanciones';

    protected static ?string $slug = 'sanciones-empleados';

    protected static string|\UnitEnum|null $navigationGroup = 'Talento Humano';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('empleado_id')
                ->label('Empleado')
                ->required()
                ->options(fn () => Empleado::query()
                    ->orderBy('apellidos')
                    ->orderBy('nombres')
                    ->get()
                    ->mapWithKeys(fn (Empleado $empleado) => [
                        $empleado->id => trim("{$empleado->codigo_empleado} - {$empleado->nombres} {$empleado->apellidos}"),
                    ])
                    ->toArray())
                ->searchable(),

            DatePicker::make('fecha')
                ->label('Fecha de sanción')
                ->required()
                ->default(now())
                ->maxDate(now()),

            Select::make('tipo')
                ->label('Tipo')
                ->required()
                ->options(SancionEmpleado::tipos())
                ->default('Llamado de atención')
                ->searchable(),

            TextInput::make('motivo')
                ->label('Motivo')
                ->required()
                ->minLength(5)
                ->maxLength(200)
                ->helperText('Explique por qué se registra la sanción o seguimiento.'),

            TextInput::make('valor_descuento')
                ->label('Valor descontado')
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->prefix('$')
                ->helperText('Use 0 si solo es advertencia, capacitación o seguimiento sin descuento.'),

            Select::make('estado')
                ->label('Estado')
                ->required()
                ->options(SancionEmpleado::estados())
                ->default('Pendiente'),

            Textarea::make('observacion')
                ->label('Observación')
                ->maxLength(500)
                ->columnSpanFull(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Información de la sanción')
                ->columns(2)
                ->schema([
                    TextEntry::make('empleado.codigo_empleado')->label('Código empleado'),
                    TextEntry::make('empleado.nombres')->label('Nombres'),
                    TextEntry::make('empleado.apellidos')->label('Apellidos'),
                    TextEntry::make('fecha')->label('Fecha')->date('d/m/Y'),
                    TextEntry::make('tipo')->label('Tipo')->badge(),
                    TextEntry::make('estado')->label('Estado')->badge()->color(fn (string $state): string => match ($state) {
                        'Aplicada' => 'danger',
                        'Pendiente' => 'warning',
                        'Anulada' => 'gray',
                        default => 'gray',
                    }),
                    TextEntry::make('valor_descuento')->label('Descuento')->money('USD'),
                    TextEntry::make('motivo')->label('Motivo')->columnSpanFull(),
                    TextEntry::make('observacion')->label('Observación')->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('empleado.codigo_empleado')->label('Código')->searchable()->sortable(),
                TextColumn::make('empleado.nombres')->label('Nombres')->searchable()->sortable(),
                TextColumn::make('empleado.apellidos')->label('Apellidos')->searchable()->sortable(),
                TextColumn::make('fecha')->label('Fecha')->date('d/m/Y')->sortable(),
                TextColumn::make('tipo')->label('Tipo')->badge()->searchable(),
                TextColumn::make('motivo')->label('Motivo')->limit(35)->searchable(),
                TextColumn::make('valor_descuento')->label('Descuento')->money('USD')->sortable(),
                TextColumn::make('estado')->label('Estado')->badge()->color(fn (string $state): string => match ($state) {
                    'Aplicada' => 'danger',
                    'Pendiente' => 'warning',
                    'Anulada' => 'gray',
                    default => 'gray',
                }),
            ])
            ->defaultSort('fecha', 'desc')
            ->recordActions([
                ViewAction::make()->label('Ver'),
                EditAction::make()->label('Editar')->visible(fn () => auth()->user()?->puedeGestionarRegistros() ?? false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Eliminar seleccionados')->visible(fn () => auth()->user()?->puedeGestionarRegistros() ?? false),
                ])->visible(fn () => auth()->user()?->puedeGestionarRegistros() ?? false),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSancionEmpleados::route('/'),
            'create' => CreateSancionEmpleado::route('/create'),
            'view' => ViewSancionEmpleado::route('/{record}'),
            'edit' => EditSancionEmpleado::route('/{record}/edit'),
        ];
    }
}
