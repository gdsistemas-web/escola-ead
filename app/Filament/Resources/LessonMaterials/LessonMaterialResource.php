<?php

namespace App\Filament\Resources\LessonMaterials;

use App\Filament\Resources\LessonMaterials\Pages\CreateLessonMaterial;
use App\Filament\Resources\LessonMaterials\Pages\EditLessonMaterial;
use App\Filament\Resources\LessonMaterials\Pages\ListLessonMaterials;
use App\Filament\Resources\LessonMaterials\Pages\ViewLessonMaterial;
use App\Filament\Resources\LessonMaterials\Schemas\LessonMaterialForm;
use App\Filament\Resources\LessonMaterials\Schemas\LessonMaterialInfolist;
use App\Filament\Resources\LessonMaterials\Tables\LessonMaterialsTable;
use App\Models\LessonMaterial;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LessonMaterialResource extends Resource
{
    protected static ?string $model = LessonMaterial::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentArrowDown;

    protected static ?string $navigationLabel = 'Materiais';

    protected static ?string $modelLabel = 'material';

    protected static ?string $pluralModelLabel = 'materiais';

    protected static \UnitEnum|string|null $navigationGroup = 'Conteúdo';

    public static function form(Schema $schema): Schema
    {
        return LessonMaterialForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return LessonMaterialInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LessonMaterialsTable::configure($table);
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
            'index' => ListLessonMaterials::route('/'),
            'create' => CreateLessonMaterial::route('/create'),
            'view' => ViewLessonMaterial::route('/{record}'),
            'edit' => EditLessonMaterial::route('/{record}/edit'),
        ];
    }
}
