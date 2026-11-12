<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->string('name')->index();
            $table->text('description')->nullable();
            $table->timestamps(0);
        });

        Schema::create('tags', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->string('name')->index();
            $table->string('color')->nullable();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps(0);
        });

        Schema::create('items', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->string('hash_code')->nullable()->index();
            $table->string('name')->index();
            $table->text('description')->nullable();
            $table->longText('additional_description')->nullable();
            $table->string('organizer')->nullable();
            $table->string('event_type', 50)->default('physical');
            $table->string('timezone')->nullable()->default('UTC');
            $table->timestamp('start_at', 0)->nullable();
            $table->timestamp('end_at', 0)->nullable();
            $table->string('status', 50)->default('draft');
            $table->string('state', 50)->default('active');
            $table->string('hero_image')->nullable();
            $table->string('virtual_link')->nullable();
            $table->string('video_url')->nullable();
            $table->integer('total_capacity')->default(0);
            $table->boolean('show_on_calendar')->default(true);
            $table->boolean('public_search')->default(false);
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->cascadeOnUpdate();
            $table->timestamps(0);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE items ADD CONSTRAINT items_status_check CHECK (status IN ('published', 'draft', 'archive', 'private', 'public'))");
            DB::statement("ALTER TABLE items ADD CONSTRAINT items_event_type_check CHECK (event_type IN ('physical', 'online', 'hybrid', 'virtual'))");
        }

        Schema::create('items_metadata', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->string('theme')->default('base');
            $table->jsonb('event_agenda')->nullable();
            $table->jsonb('event_faqs')->nullable();
            $table->jsonb('event_host')->nullable();
            $table->longText('refund_policy')->nullable();
            $table->string('google_calendar_id')->nullable()->index();
            $table->string('google_analytics_id')->nullable();
            $table->boolean('show_on_collection')->default(true);
            $table->boolean('show_latest_announcement')->default(false);
            $table->boolean('custom_book_button_label')->default(false);
            $table->string('book_button_label')->nullable();
            $table->string('book_button_color')->nullable();
            $table->string('book_promotion_label')->nullable();
            $table->timestamps(0);
        });

        Schema::create('item_tag', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->timestamps(0);
        });

        Schema::create('item_assets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->string('type')->nullable();
            $table->string('additional_asset')->nullable();
            $table->timestamps(0);
            $table->softDeletes();
        });

        Schema::create('venues', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->string('name')->nullable();
            $table->string('address_name')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('long', 10, 7)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->timestamps(0);
        });

        Schema::create('item_venue', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('venue_id')->constrained('venues')->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->timestamps(0);
        });

        Schema::create('announcements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->text('message');
            $table->string('importance_type', 50)->nullable()->default('low');
            $table->boolean('delivered')->default(false);
            $table->timestamp('scheduled_at', 0)->nullable();
            $table->timestamps(0);
        });

        Schema::create('speakers', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('profile_image')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('email')->nullable();
            $table->string('timezone')->nullable();
            $table->text('biography')->nullable();
            $table->text('expertise')->nullable();
            $table->boolean('virtual')->default(false);
            $table->boolean('physical')->default(false);
            $table->boolean('podcast')->default(false);
            $table->jsonb('contacts')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('contractor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps(0);
            $table->softDeletes();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE speakers ALTER COLUMN email TYPE citext');
        }

        Schema::create('speaker_category', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('speaker_id')->constrained('speakers')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->timestamps(0);
        });

        Schema::create('item_speaker', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('speaker_id')->constrained('speakers')->cascadeOnDelete();
            $table->timestamps(0);
        });

        Schema::create('sponsors', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->string('name');
            $table->string('label')->nullable();
            $table->string('email')->nullable();
            $table->string('logo')->nullable();
            $table->text('summary')->nullable();
            $table->string('country')->nullable();
            $table->boolean('virtual')->default(false);
            $table->boolean('physical')->default(false);
            $table->boolean('hybrid')->default(false);
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('contractor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps(0);
            $table->softDeletes();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE sponsors ALTER COLUMN email TYPE citext');
        }

        Schema::create('sponsor_category', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sponsor_id')->constrained('sponsors')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->timestamps(0);
        });

        Schema::create('item_sponsor', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('sponsor_id')->constrained('sponsors')->cascadeOnDelete();
            $table->timestamps(0);
        });

        Schema::create('surveys', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->longText('json')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->timestamps(0);
            $table->softDeletes();
        });

        Schema::create('item_survey', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('survey_id')->constrained('surveys')->cascadeOnDelete();
            $table->longText('json')->nullable();
            $table->timestamps(0);
        });

        Schema::create('todos', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status', 50)->default('pending');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('todos');
        Schema::dropIfExists('item_survey');
        Schema::dropIfExists('surveys');
        Schema::dropIfExists('item_sponsor');
        Schema::dropIfExists('sponsor_category');
        Schema::dropIfExists('sponsors');
        Schema::dropIfExists('item_speaker');
        Schema::dropIfExists('speaker_category');
        Schema::dropIfExists('speakers');
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('item_venue');
        Schema::dropIfExists('venues');
        Schema::dropIfExists('item_assets');
        Schema::dropIfExists('item_tag');
        Schema::dropIfExists('items_metadata');
        Schema::dropIfExists('items');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('categories');
    }
};
