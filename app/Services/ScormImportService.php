<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Lesson;
use App\Models\ScormPackage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use ZipArchive;

class ScormImportService
{
    private const BLOCKED_EXTENSIONS = ['php', 'phtml', 'phar', 'exe', 'bat', 'cmd', 'com', 'scr', 'msi', 'ps1', 'sh'];

    public function import(UploadedFile $file, User $teacher, int $categoryId, ?string $courseName = null): Course
    {
        $zip = new ZipArchive();

        if ($zip->open($file->getRealPath()) !== true) {
            throw new RuntimeException('Nao foi possivel abrir o arquivo ZIP.');
        }

        try {
            $this->validateZip($zip);
            $manifestName = $this->findManifest($zip);
            $manifestXml = $zip->getFromName($manifestName);

            if (! $manifestXml) {
                throw new RuntimeException('O manifesto SCORM esta vazio ou ilegivel.');
            }

            $manifest = $this->parseManifest($manifestXml);
            $uuid = (string) Str::uuid();
            $storagePath = "scorm/{$uuid}";
            $absoluteTarget = Storage::disk('public')->path($storagePath);

            File::ensureDirectoryExists($absoluteTarget);
            $this->extractZip($zip, $absoluteTarget);

            try {
                return DB::transaction(function () use ($file, $teacher, $categoryId, $courseName, $manifest, $storagePath, $manifestName) {
                    $courseTitle = filled($courseName) ? $courseName : $manifest['title'];

                    $course = Course::create([
                        'category_id' => $categoryId,
                        'teacher_id' => $teacher->id,
                        'name' => $courseTitle,
                        'slug' => $this->uniqueSlug($courseTitle),
                        'short_description' => 'Curso importado de pacote SCORM.',
                        'description' => "Curso criado automaticamente a partir do pacote SCORM {$file->getClientOriginalName()}.",
                        'workload_hours' => 0,
                        'minimum_grade' => 7,
                        'minimum_progress_percent' => 75,
                        'status' => 'draft',
                        'is_featured' => false,
                    ]);

                    $module = CourseModule::create([
                        'course_id' => $course->id,
                        'title' => $manifest['module_title'] ?: 'Conteudo SCORM',
                        'description' => 'Modulo importado automaticamente do manifesto SCORM.',
                        'position' => 1,
                        'is_available' => true,
                        'status' => 'published',
                    ]);

                    foreach ($manifest['items'] as $index => $item) {
                        $lesson = Lesson::create([
                            'course_module_id' => $module->id,
                            'title' => $item['title'] ?: 'Aula '.($index + 1),
                            'description' => 'Aula SCORM importada automaticamente.',
                            'content_type' => 'scorm',
                            'content_url' => "/storage/{$storagePath}/{$item['href']}",
                            'file_path' => "{$storagePath}/{$item['href']}",
                            'duration_minutes' => 0,
                            'position' => $index + 1,
                            'is_required' => true,
                            'is_available' => true,
                        ]);

                        $package = ScormPackage::create([
                            'course_id' => $course->id,
                            'lesson_id' => $lesson->id,
                            'uploaded_by' => $teacher->id,
                            'title' => $item['title'] ?: $courseTitle,
                            'version' => $manifest['version'],
                            'manifest_path' => "{$storagePath}/{$manifestName}",
                            'launch_path' => "{$storagePath}/{$item['href']}",
                            'storage_path' => $storagePath,
                            'original_filename' => $file->getClientOriginalName(),
                            'size_bytes' => $file->getSize(),
                            'status' => 'valid',
                            'metadata' => [
                                'resource_identifier' => $item['identifierref'],
                                'manifest_title' => $manifest['title'],
                            ],
                        ]);

                        $lesson->update(['content_url' => "/storage/{$package->launch_path}", 'file_path' => $package->launch_path]);
                    }

                    return $course->load('category', 'teacher')->loadCount('modules');
                });
            } catch (Throwable $exception) {
                Storage::disk('public')->deleteDirectory($storagePath);
                throw $exception;
            }
        } finally {
            $zip->close();
        }
    }

    private function validateZip(ZipArchive $zip): void
    {
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = str_replace('\\', '/', $zip->getNameIndex($index));
            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

            if (str_contains($name, '../') || str_starts_with($name, '/') || preg_match('/^[a-zA-Z]:\//', $name)) {
                throw new RuntimeException('O ZIP contem caminhos invalidos.');
            }

            if (in_array($extension, self::BLOCKED_EXTENSIONS, true)) {
                throw new RuntimeException("O pacote contem arquivo nao permitido: {$name}");
            }
        }
    }

    private function findManifest(ZipArchive $zip): string
    {
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = str_replace('\\', '/', $zip->getNameIndex($index));

            if (strtolower(basename($name)) === 'imsmanifest.xml') {
                return $name;
            }
        }

        throw new RuntimeException('Pacote SCORM invalido: imsmanifest.xml nao encontrado.');
    }

    private function parseManifest(string $xml): array
    {
        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);

        if (! $dom->loadXML($xml)) {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
            throw new RuntimeException('Nao foi possivel ler o imsmanifest.xml.');
        }

        libxml_use_internal_errors($previous);

        $xpath = new \DOMXPath($dom);
        $resources = [];

        foreach ($xpath->query('//*[local-name()="resources"]/*[local-name()="resource"]') as $resource) {
            $identifier = $resource->attributes?->getNamedItem('identifier')?->nodeValue;
            $href = $this->normalizePath($resource->attributes?->getNamedItem('href')?->nodeValue ?? '');

            if ($identifier && $href) {
                $resources[$identifier] = $href;
            }
        }

        $items = [];
        foreach ($xpath->query('//*[local-name()="organizations"]/*[local-name()="organization"]//*[local-name()="item"]') as $item) {
            $identifierRef = $item->attributes?->getNamedItem('identifierref')?->nodeValue;
            $href = $identifierRef ? ($resources[$identifierRef] ?? null) : null;

            if ($href) {
                $items[] = [
                    'title' => trim($xpath->evaluate('string(./*[local-name()="title"][1])', $item)),
                    'href' => $href,
                    'identifierref' => $identifierRef,
                ];
            }
        }

        if (! $items && $resources) {
            $identifierRef = array_key_first($resources);
            $items[] = [
                'title' => '',
                'href' => $resources[$identifierRef],
                'identifierref' => $identifierRef,
            ];
        }

        if (! $items) {
            throw new RuntimeException('Nenhum SCO com arquivo inicial foi encontrado no manifesto.');
        }

        return [
            'title' => trim($xpath->evaluate('string((//*[local-name()="organizations"]/*[local-name()="organization"]/*[local-name()="title"])[1])')) ?: 'Curso SCORM',
            'module_title' => trim($xpath->evaluate('string((//*[local-name()="organizations"]/*[local-name()="organization"]/*[local-name()="title"])[1])')),
            'version' => trim($xpath->evaluate('string((//*[local-name()="schemaversion"])[1])')) ?: null,
            'items' => $items,
        ];
    }

    private function extractZip(ZipArchive $zip, string $absoluteTarget): void
    {
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = str_replace('\\', '/', $zip->getNameIndex($index));

            if (str_ends_with($name, '/')) {
                continue;
            }

            $target = $absoluteTarget.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $name);
            File::ensureDirectoryExists(dirname($target));

            $stream = $zip->getStream($name);
            if (! $stream) {
                throw new RuntimeException("Nao foi possivel extrair {$name}.");
            }

            file_put_contents($target, stream_get_contents($stream));
            fclose($stream);
        }
    }

    private function normalizePath(string $path): string
    {
        return ltrim(str_replace('\\', '/', rawurldecode($path)), '/');
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'curso-scorm';
        $slug = $base;
        $counter = 2;

        while (Course::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
