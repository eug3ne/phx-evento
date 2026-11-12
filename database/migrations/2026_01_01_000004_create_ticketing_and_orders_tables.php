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
        Schema::create('tickets', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->string('name')->index();
            $table->text('description')->nullable();
            $table->boolean('is_ticket')->default(true);
            $table->boolean('is_reservation')->default(false);
            $table->boolean('is_artwork')->default(false);
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->timestamps(0);
        });

        Schema::create('item_ticket', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->decimal('price', 14, 2)->default(0.00);
            $table->decimal('final_price', 14, 2)->default(0.00);
            $table->text('custom_description')->nullable();
            $table->integer('total_quantity')->default(0);
            $table->integer('sold_quantity')->default(0);
            $table->boolean('absorb_service_fee')->default(false);
            $table->boolean('is_reservation')->default(false);
            $table->boolean('is_artwork')->default(false);
            $table->boolean('personalized_ticket')->default(false);
            $table->boolean('phone_number_enabled')->default(false);
            $table->integer('max_quantity_per_person')->default(10);
            $table->string('floor_plan_image')->nullable();
            $table->string('attendance_type', 50)->nullable();
            $table->timestamp('start_sale_at', 0)->nullable();
            $table->timestamp('end_sale_at', 0)->nullable();
            $table->timestamps(0);
        });

        Schema::create('ticket_assets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->jsonb('assets')->nullable();
            $table->timestamps(0);
        });

        Schema::create('discounts', function (Blueprint $table): void {
            $table->id();
            $table->jsonb('payload')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('items')->cascadeOnDelete();
            $table->timestamps(0);
            $table->softDeletes();
        });

        Schema::create('affiliates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('code', 100)->index();
            $table->string('email')->nullable();
            $table->integer('total_sales')->default(0);
            $table->decimal('total_sales_gross', 14, 2)->default(0.00);
            $table->string('status', 30)->default('ACTIVE');
            $table->timestamps(0);
            $table->unique(['event_id', 'code']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE affiliates ALTER COLUMN email TYPE citext');
            DB::statement("ALTER TABLE affiliates ADD CONSTRAINT affiliates_status_check CHECK (status IN ('ACTIVE', 'INACTIVE'))");
        }

        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->string('hash_code')->nullable()->index();
            $table->string('slug')->nullable();
            $table->longText('payload')->nullable();
            $table->jsonb('json')->nullable();
            $table->string('status', 40)->default('COMPLETED')->index();
            $table->decimal('total_gross', 14, 2)->default(0.00);
            $table->decimal('total_before_additions', 14, 2)->default(0.00);
            $table->decimal('total_tax', 14, 2)->default(0.00);
            $table->decimal('total_fee', 14, 2)->default(0.00);
            $table->decimal('total_refunded', 14, 2)->default(0.00);
            $table->foreignId('affiliate_id')->nullable()->constrained('affiliates')->nullOnDelete();
            $table->string('utm_source')->nullable()->index();
            $table->string('utm_medium')->nullable()->index();
            $table->string('utm_campaign')->nullable()->index();
            $table->string('utm_term')->nullable();
            $table->string('utm_content')->nullable();
            $table->string('gclid')->nullable();
            $table->string('fbclid')->nullable();
            $table->text('referrer_url')->nullable();
            $table->timestamp('statistics_decremented_at', 0)->nullable();
            $table->timestamps(0);
            $table->softDeletes();
        });

        Schema::create('item_order', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->timestamps(0);
        });

        Schema::create('attendees', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->string('ticket_number')->nullable()->index();
            $table->string('name');
            $table->string('email')->index();
            $table->string('phone_number')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('price', 14, 2)->default(0.00);
            $table->decimal('final_price', 14, 2)->default(0.00);
            $table->boolean('check_in')->default(false);
            $table->integer('checked_in_qty')->default(0);
            $table->boolean('has_discount')->default(false);
            $table->text('special_requirements')->nullable();
            $table->string('dietary_requirements')->nullable();
            $table->string('attendance_type', 50)->nullable()->default('in_person');
            $table->boolean('is_paid')->default(false);
            $table->boolean('is_confirmed')->default(false);
            $table->boolean('is_cancelled')->default(false);
            $table->timestamp('confirmed_at', 0)->nullable();
            $table->timestamp('paid_at', 0)->nullable();
            $table->timestamp('cancelled_at', 0)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->timestamps(0);
            $table->softDeletes();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE attendees ALTER COLUMN email TYPE citext');
        }

        Schema::create('attendees_metadata', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('attendee_id')->constrained('attendees')->cascadeOnDelete();
            $table->string('snipcart_id')->nullable();
            $table->string('transaction_id')->nullable()->index();
            $table->timestamps(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendees_metadata');
        Schema::dropIfExists('attendees');
        Schema::dropIfExists('item_order');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('affiliates');
        Schema::dropIfExists('discounts');
        Schema::dropIfExists('ticket_assets');
        Schema::dropIfExists('item_ticket');
        Schema::dropIfExists('tickets');
    }
};
