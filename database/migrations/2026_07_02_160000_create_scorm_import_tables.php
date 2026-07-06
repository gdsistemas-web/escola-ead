<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE lessons MODIFY content_type ENUM('youtube','vimeo','mp4','pdf','docx','pptx','external_link','scorm') DEFAULT 'youtube'");
        }

        Schema::create('scorm_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->string('title');
            $table->string('version', 20)->nullable();
            $table->string('manifest_path');
            $table->string('launch_path');
            $table->string('storage_path');
            $table->string('original_filename');
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->enum('status', ['processing', 'valid', 'invalid', 'published'])->default('valid');
            $table->json('validation_errors')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('scorm_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scorm_package_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['not_attempted', 'incomplete', 'completed', 'passed', 'failed'])->default('not_attempted');
            $table->decimal('score_raw', 6, 2)->nullable();
            $table->decimal('score_min', 6, 2)->nullable();
            $table->decimal('score_max', 6, 2)->nullable();
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->unsignedInteger('session_time_seconds')->default(0);
            $table->unsignedInteger('total_time_seconds')->default(0);
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();
            $table->unique(['scorm_package_id', 'user_id']);
        });

        Schema::create('scorm_sco_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scorm_attempt_id')->constrained()->cascadeOnDelete();
            $table->string('element');
            $table->longText('value')->nullable();
            $table->timestamps();
            $table->unique(['scorm_attempt_id', 'element']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scorm_sco_data');
        Schema::dropIfExists('scorm_attempts');
        Schema::dropIfExists('scorm_packages');

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE lessons MODIFY content_type ENUM('youtube','vimeo','mp4','pdf','docx','pptx','external_link') DEFAULT 'youtube'");
        }
    }
};
