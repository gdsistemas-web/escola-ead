<?php

namespace App\Filament\Resources\LessonMaterials\Pages;

use App\Filament\Resources\LessonMaterials\LessonMaterialResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewLessonMaterial extends ViewRecord
{
    protected static string $resource = LessonMaterialResource::class;

    public function getTitle(): string
    {
        return 'Detalhes do material';
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('Editar'),
        ];
    }
}
