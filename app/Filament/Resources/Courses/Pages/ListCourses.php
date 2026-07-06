<?php

namespace App\Filament\Resources\Courses\Pages;

use App\Filament\Resources\Courses\CourseResource;
use App\Filament\Resources\Courses\Widgets\CourseStatsOverview;
use App\Filament\Support\ListPageActions;
use App\Models\User;
use App\Services\ScormImportService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;

class ListCourses extends ListRecords
{
    protected static string $resource = CourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...ListPageActions::make('cursos', 'courses'),
            Action::make('import_scorm')
                ->label('Importar SCORM')
                ->icon('heroicon-m-arrow-up-tray')
                ->color('gray')
                ->outlined()
                ->modalHeading('Importar pacote SCORM')
                ->modalDescription('O pacote sera lido pelo manifesto e criado como curso em rascunho.')
                ->modalSubmitActionLabel('Importar pacote')
                ->schema([
                    Select::make('category_id')
                        ->label('Categoria')
                        ->relationship('category', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Select::make('teacher_id')
                        ->label('Professor responsavel')
                        ->options(fn () => User::role('professor')->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->default(auth()->id())
                        ->required(),
                    TextInput::make('course_name')
                        ->label('Nome do curso')
                        ->placeholder('Deixe vazio para usar o titulo do manifesto')
                        ->maxLength(255),
                    FileUpload::make('package')
                        ->label('Pacote SCORM (.zip)')
                        ->disk('local')
                        ->directory('tmp/scorm-imports')
                        ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed', 'multipart/x-zip'])
                        ->maxSize(204800)
                        ->required(),
                ])
                ->action(function (array $data, ScormImportService $importer): void {
                    $path = is_array($data['package']) ? reset($data['package']) : $data['package'];
                    $absolutePath = Storage::disk('local')->path($path);
                    $teacher = User::findOrFail($data['teacher_id']);

                    $file = new \Illuminate\Http\UploadedFile(
                        $absolutePath,
                        basename($path),
                        'application/zip',
                        null,
                        true,
                    );

                    try {
                        $course = $importer->import($file, $teacher, (int) $data['category_id'], $data['course_name'] ?? null);
                    } finally {
                        Storage::disk('local')->delete($path);
                    }

                    Notification::make()
                        ->title('SCORM importado')
                        ->body("Curso criado como rascunho: {$course->name}")
                        ->success()
                        ->send();
                }),
            CreateAction::make()
                ->label('Novo curso'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CourseStatsOverview::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        return 4;
    }
}
