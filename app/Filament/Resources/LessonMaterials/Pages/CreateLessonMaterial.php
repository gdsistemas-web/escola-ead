<?php

namespace App\Filament\Resources\LessonMaterials\Pages;

use App\Filament\Resources\LessonMaterials\LessonMaterialResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLessonMaterial extends CreateRecord
{
    protected static string $resource = LessonMaterialResource::class;

    public function getTitle(): string
    {
        return 'Novo material';
    }
}
