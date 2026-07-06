<?php

namespace App\Filament\Resources\LessonMaterials\Pages;

use App\Filament\Resources\LessonMaterials\LessonMaterialResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditLessonMaterial extends EditRecord
{
    protected static string $resource = LessonMaterialResource::class;

    public function getTitle(): string
    {
        return 'Editar material';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->label('Ver'),
            DeleteAction::make()->label('Excluir'),
        ];
    }
}
