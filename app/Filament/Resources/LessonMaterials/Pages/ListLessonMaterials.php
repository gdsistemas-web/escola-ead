<?php

namespace App\Filament\Resources\LessonMaterials\Pages;

use App\Filament\Resources\LessonMaterials\LessonMaterialResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLessonMaterials extends ListRecords
{
    protected static string $resource = LessonMaterialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Novo material'),
        ];
    }
}
