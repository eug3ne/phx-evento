<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_statistics', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->unique()->constrained('items')->cascadeOnDelete();
            $table->integer('products_sold')->default(0);
            $table->integer('attendees_registered')->default(0);
            $table->decimal('sales_total_gross', 14, 2)->default(0.00);
            $table->decimal('sales_total_before_additions', 14, 2)->default(0.00);
            $table->decimal('total_tax', 14, 2)->default(0.00);
            $table->decimal('total_fee', 14, 2)->default(0.00);
            $table->decimal('total_refunded', 14, 2)->default(0.00);
            $table->integer('orders_created')->default(0);
            $table->integer('orders_cancelled')->default(0);
            $table->integer('version')->default(1);
            $table->timestamps(0);
            $table->softDeletes();
        });

        Schema::create('event_daily_statistics', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->constrained('items')->cascadeOnDelete();
            $table->date('date')->index();
            $table->integer('products_sold')->default(0);
            $table->integer('attendees_registered')->default(0);
            $table->decimal('sales_total_gross', 14, 2)->default(0.00);
            $table->decimal('sales_total_before_additions', 14, 2)->default(0.00);
            $table->decimal('total_tax', 14, 2)->default(0.00);
            $table->decimal('total_fee', 14, 2)->default(0.00);
            $table->decimal('total_refunded', 14, 2)->default(0.00);
            $table->integer('orders_created')->default(0);
            $table->integer('orders_cancelled')->default(0);
            $table->integer('version')->default(1);
            $table->timestamps(0);
            $table->softDeletes();
            $table->unique(['event_id', 'date']);
        });

        Schema::create('check_in_lists', function (Blueprint $table): void {
            $table->id();
            $table->string('short_id', 64)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('event_id')->constrained('items')->cascadeOnDelete();
            $table->timestamp('activates_at', 0)->nullable();
            $table->timestamp('expires_at', 0)->nullable();
            $table->timestamps(0);
            $table->softDeletes();
        });

        Schema::create('waitlist_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('ticket_id')->nullable()->constrained('tickets')->nullOnDelete();
            $table->string('email')->index();
            $table->string('name')->nullable();
            $table->integer('quantity')->default(1);
            $table->string('status', 40)->default('WAITING')->index();
            $table->timestamps(0);
        });

        Schema::create('webhooks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('event_id')->nullable()->constrained('items')->cascadeOnDelete();
            $table->string('url');
            $table->jsonb('event_types');
            $table->string('secret', 120);
            $table->string('status', 30)->default('ENABLED');
            $table->timestamp('last_triggered_at', 0)->nullable();
            $table->integer('last_response_code')->nullable();
            $table->timestamps(0);
            $table->softDeletes();
        });

        Schema::create('webhook_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('webhook_id')->constrained('webhooks')->cascadeOnDelete();
            $table->string('event_type');
            $table->jsonb('payload')->nullable();
            $table->integer('response_code')->nullable();
            $table->text('response_body')->nullable();
            $table->timestamps(0);
        });

        Schema::create('email_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->nullable()->constrained('items')->nullOnDelete();
            $table->string('provider', 50)->default('sendgrid');
            $table->string('provider_message_id')->nullable()->index();
            $table->string('recipient_email')->index();
            $table->string('event_type', 50)->index();
            $table->jsonb('metadata')->nullable();
            $table->timestamp('occurred_at', 0)->nullable();
            $table->timestamps(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_deliveries');
        Schema::dropIfExists('webhook_logs');
        Schema::dropIfExists('webhooks');
        Schema::dropIfExists('waitlist_entries');
        Schema::dropIfExists('check_in_lists');
        Schema::dropIfExists('event_daily_statistics');
        Schema::dropIfExists('event_statistics');
    }
};
