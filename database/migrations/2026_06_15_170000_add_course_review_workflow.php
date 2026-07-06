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
            DB::statement("ALTER TABLE courses MODIFY status ENUM('draft', 'pending_review', 'changes_requested', 'published', 'closed') NOT NULL DEFAULT 'draft'");
        }

        Schema::table('courses', function (Blueprint $table) {
            if (! Schema::hasColumn('courses', 'submitted_for_review_at')) {
                $table->timestamp('submitted_for_review_at')->nullable()->after('is_featured');
            }
            if (! Schema::hasColumn('courses', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('submitted_for_review_at');
            }
            if (! Schema::hasColumn('courses', 'reviewed_by')) {
                $table->foreignId('reviewed_by')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('courses', 'review_notes')) {
                $table->text('review_notes')->nullable()->after('reviewed_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            foreach (['review_notes', 'reviewed_by', 'reviewed_at', 'submitted_for_review_at'] as $column) {
                if (Schema::hasColumn('courses', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE courses MODIFY status ENUM('draft', 'published', 'closed') NOT NULL DEFAULT 'draft'");
        }
    }
};
