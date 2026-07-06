<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('user_profiles', 'lgpd_consent_version')) {
                $table->string('lgpd_consent_version')->nullable()->after('lgpd_consent_at');
            }
            if (! Schema::hasColumn('user_profiles', 'privacy_policy_version')) {
                $table->string('privacy_policy_version')->nullable()->after('terms_accepted_at');
            }
            if (! Schema::hasColumn('user_profiles', 'data_exported_at')) {
                $table->timestamp('data_exported_at')->nullable();
            }
            if (! Schema::hasColumn('user_profiles', 'anonymization_requested_at')) {
                $table->timestamp('anonymization_requested_at')->nullable();
            }
            if (! Schema::hasColumn('user_profiles', 'anonymized_at')) {
                $table->timestamp('anonymized_at')->nullable();
            }
        });

        Schema::table('certificates', function (Blueprint $table) {
            if (! Schema::hasColumn('certificates', 'verification_hash')) {
                $table->string('verification_hash', 64)->nullable()->unique()->after('code');
            }
            if (! Schema::hasColumn('certificates', 'status')) {
                $table->enum('status', ['valid', 'revoked'])->default('valid')->after('pdf_path')->index();
            }
            if (! Schema::hasColumn('certificates', 'revoked_at')) {
                $table->timestamp('revoked_at')->nullable();
            }
            if (! Schema::hasColumn('certificates', 'revoked_reason')) {
                $table->text('revoked_reason')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            foreach (['verification_hash', 'status', 'revoked_at', 'revoked_reason'] as $column) {
                if (Schema::hasColumn('certificates', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('user_profiles', function (Blueprint $table) {
            foreach (['lgpd_consent_version', 'privacy_policy_version', 'data_exported_at', 'anonymization_requested_at', 'anonymized_at'] as $column) {
                if (Schema::hasColumn('user_profiles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
