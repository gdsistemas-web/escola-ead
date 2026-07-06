<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            if (! Schema::hasColumn('enrollments', 'application_data')) {
                $table->json('application_data')->nullable()->after('source');
            }

            if (! Schema::hasColumn('enrollments', 'terms_accepted_at')) {
                $table->timestamp('terms_accepted_at')->nullable()->after('application_data');
            }
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            foreach (['terms_accepted_at', 'application_data'] as $column) {
                if (Schema::hasColumn('enrollments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
