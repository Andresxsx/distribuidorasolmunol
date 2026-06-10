<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;

class Reportes extends Page
{
    protected static ?string $navigationLabel = 'Reportes';

    protected static ?string $title = 'Reportes';

    protected static ?string $slug = 'reportes';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static string|\UnitEnum|null $navigationGroup = 'Dirección';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.reportes';

    public static function canAccess(): bool
    {
        return auth()->check()
            && in_array(auth()->user()->rol, ['Administrador', 'Directivo'], true);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check()
            && in_array(auth()->user()->rol, ['Administrador', 'Directivo'], true);
    }

    public function getViewData(): array
    {
        return [
            'usuario' => auth()->user(),
            'fechaHora' => now()->format('d/m/Y H:i:s'),
        ];
    }
}