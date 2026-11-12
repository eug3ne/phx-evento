<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->string('name');
            $table->string('slug')->nullable()->unique();
            $table->string('stripe_plan')->nullable();
            $table->decimal('cost', 14, 2)->default(0.00);
            $table->text('description')->nullable();
            $table->timestamps(0);
        });

        Schema::create('vendors', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->string('name');
            $table->string('vendor_type')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('currency', 10)->default('aud');
            $table->string('postal_code')->nullable();
            $table->string('bank_account')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('account_number')->nullable();
            $table->string('routing_number')->nullable();
            $table->string('company_name')->nullable();
            $table->string('vat_number')->nullable();
            $table->string('status', 50)->default('active');
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->timestamps(0);
        });

        Schema::create('fees', function (Blueprint $table): void {
            $table->id();
            $table->string('type')->default('service');
            $table->decimal('percentage_fee', 14, 2)->default(0.00);
            $table->decimal('fixed_fee', 14, 2)->default(0.00);
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->timestamps(0);
        });

        Schema::create('payouts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->decimal('gross_amount', 14, 2)->default(0.00);
            $table->decimal('net_amount', 14, 2)->default(0.00);
            $table->decimal('after_fees_amount', 14, 2)->default(0.00);
            $table->decimal('paid_amount', 14, 2)->default(0.00);
            $table->boolean('has_discount')->default(false)->index();
            $table->integer('total_tickets')->default(0);
            $table->string('payment_provider')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_id')->nullable();
            $table->integer('ticket_id')->nullable();
            $table->string('currency', 10)->default('aud')->index();
            $table->string('status', 50)->default('pending');
            $table->longText('webhook_payload')->nullable();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->timestamp('refund_at', 0)->nullable();
            $table->timestamps(0);
            $table->softDeletes();
        });

        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->string('trx_id')->index();
            $table->string('event_type')->nullable();
            $table->longText('payload')->nullable();
            $table->jsonb('json')->nullable();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->timestamps(0);
            $table->softDeletes();
        });

        Schema::create('currencies', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code', 10)->index();
            $table->string('symbol', 25)->nullable();
            $table->string('format', 50)->nullable();
            $table->decimal('exchange_rate', 14, 6)->default(1.000000);
            $table->boolean('active')->default(true);
            $table->timestamps(0);
        });

        Schema::create('subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id');
            $table->string('type')->default('default');
            $table->string('stripe_id')->unique();
            $table->string('stripe_status');
            $table->string('stripe_price')->nullable();
            $table->integer('quantity')->nullable();
            $table->timestamp('trial_ends_at', 0)->nullable();
            $table->timestamp('ends_at', 0)->nullable();
            $table->timestamps(0);
            $table->index(['user_id', 'stripe_status']);
        });

        Schema::create('subscription_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('subscription_id');
            $table->string('stripe_id')->unique();
            $table->string('stripe_product');
            $table->string('stripe_price');
            $table->integer('quantity')->nullable();
            $table->timestamps(0);
            $table->index(['subscription_id', 'stripe_price']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_items');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('currencies');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('payouts');
        Schema::dropIfExists('fees');
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('plans');
    }
};
