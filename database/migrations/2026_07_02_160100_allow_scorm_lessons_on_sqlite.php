<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        Schema::disableForeignKeyConstraints();

        Schema::create('lessons_rebuild', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_module_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('content_type')->default('youtube');
            $table->string('content_url')->nullable();
            $table->string('file_path')->nullable();
            $table->unsignedInteger('duration_minutes')->default(0);
            $table->unsignedInteger('position')->default(1);
            $table->boolean('is_required')->default(true);
            $table->boolean('is_available')->default(true);
            $table->timestamps();
        });

        DB::statement('
            INSERT INTO lessons_rebuild (
                id, course_module_id, title, description, content_type, content_url, file_path,
                duration_minutes, position, is_required, is_available, created_at, updated_at
            )
            SELECT
                id, course_module_id, title, description, content_type, content_url, file_path,
                duration_minutes, position, is_required, is_available, created_at, updated_at
            FROM lessons
        ');

        Schema::drop('lessons');
        Schema::rename('lessons_rebuild', 'lessons');

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        //
    }
};
