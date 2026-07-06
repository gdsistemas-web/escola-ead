<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forum_categories', function (Blueprint $table) {
            if (! Schema::hasColumn('forum_categories', 'slug')) {
                $table->string('slug')->nullable()->after('name');
            }
            if (! Schema::hasColumn('forum_categories', 'type')) {
                $table->enum('type', ['institutional', 'course', 'lesson'])->default('course')->after('course_id');
            }
            if (! Schema::hasColumn('forum_categories', 'lesson_id')) {
                $table->foreignId('lesson_id')->nullable()->after('course_id')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('forum_categories', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
            if (! Schema::hasColumn('forum_categories', 'allow_student_topics')) {
                $table->boolean('allow_student_topics')->default(true);
            }
        });

        Schema::create('forum_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('is_enabled')->default(true);
            $table->boolean('auto_create_lesson_forums')->default(true);
            $table->json('default_sections')->nullable();
            $table->timestamps();
        });

        Schema::table('forum_topics', function (Blueprint $table) {
            if (! Schema::hasColumn('forum_topics', 'course_id')) {
                $table->foreignId('course_id')->nullable()->after('forum_category_id')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('forum_topics', 'lesson_id')) {
                $table->foreignId('lesson_id')->nullable()->after('course_id')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('forum_topics', 'status')) {
                $table->enum('status', ['open', 'closed', 'resolved', 'pinned', 'hidden'])->default('open')->after('body');
            }
            if (! Schema::hasColumn('forum_topics', 'type')) {
                $table->enum('type', ['discussion', 'question', 'announcement', 'assessment'])->default('discussion')->after('status');
            }
            if (! Schema::hasColumn('forum_topics', 'is_assessment')) {
                $table->boolean('is_assessment')->default(false);
            }
            if (! Schema::hasColumn('forum_topics', 'assessment_points')) {
                $table->decimal('assessment_points', 6, 2)->nullable();
            }
            if (! Schema::hasColumn('forum_topics', 'assessment_due_at')) {
                $table->timestamp('assessment_due_at')->nullable();
            }
            if (! Schema::hasColumn('forum_topics', 'requires_reply')) {
                $table->boolean('requires_reply')->default(false);
            }
            if (! Schema::hasColumn('forum_topics', 'views_count')) {
                $table->unsignedInteger('views_count')->default(0);
            }
            if (! Schema::hasColumn('forum_topics', 'replies_count')) {
                $table->unsignedInteger('replies_count')->default(0);
            }
            if (! Schema::hasColumn('forum_topics', 'last_activity_at')) {
                $table->timestamp('last_activity_at')->nullable();
            }
        });

        Schema::create('forum_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('forum_topic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('forum_reply_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['topic', 'reply'])->default('reply');
            $table->longText('body');
            $table->json('attachments')->nullable();
            $table->boolean('is_hidden')->default(false);
            $table->boolean('is_best_answer')->default(false);
            $table->timestamps();
        });

        Schema::table('forum_replies', function (Blueprint $table) {
            if (! Schema::hasColumn('forum_replies', 'parent_id')) {
                $table->foreignId('parent_id')->nullable()->after('forum_topic_id')->constrained('forum_replies')->nullOnDelete();
            }
            if (! Schema::hasColumn('forum_replies', 'attachments')) {
                $table->json('attachments')->nullable();
            }
            if (! Schema::hasColumn('forum_replies', 'is_hidden')) {
                $table->boolean('is_hidden')->default(false);
            }
            if (! Schema::hasColumn('forum_replies', 'hidden_at')) {
                $table->timestamp('hidden_at')->nullable();
            }
        });

        Schema::create('forum_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('color', 20)->default('#008f43');
            $table->timestamps();
        });

        Schema::create('forum_topic_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('forum_topic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('forum_tag_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['forum_topic_id', 'forum_tag_id']);
        });

        Schema::table('forum_likes', function (Blueprint $table) {
            if (! Schema::hasColumn('forum_likes', 'forum_topic_id')) {
                $table->foreignId('forum_topic_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            }
            if (! Schema::hasColumn('forum_likes', 'reaction')) {
                $table->enum('reaction', ['liked', 'useful', 'excellent'])->default('liked')->after('user_id');
            }
        });

        Schema::create('forum_mentions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('forum_topic_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('forum_reply_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('mentioned_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('mentioned_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('forum_badges', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->default('ri-award-line');
            $table->unsignedInteger('points_required')->default(0);
            $table->timestamps();
        });

        Schema::create('forum_user_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('forum_badge_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('awarded_at')->useCurrent();
            $table->timestamps();
            $table->unique(['forum_badge_id', 'user_id']);
        });

        Schema::create('forum_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('forum_topic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamps();
        });

        Schema::create('forum_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('forum_topic_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('forum_reply_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('url')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('forum_warnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('issued_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('forum_topic_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('forum_reply_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reason');
            $table->text('notes')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('forum_reputation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('source_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->nullableMorphs('subject');
            $table->string('event');
            $table->integer('points');
            $table->timestamps();
        });

        Schema::create('forum_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event');
            $table->nullableMorphs('subject');
            $table->json('properties')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ([
            'forum_activity_logs',
            'forum_reputation',
            'forum_warnings',
            'forum_notifications',
            'forum_views',
            'forum_user_badges',
            'forum_badges',
            'forum_mentions',
            'forum_topic_tags',
            'forum_tags',
            'forum_posts',
            'forum_courses',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::enableForeignKeyConstraints();
    }
};
