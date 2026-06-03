<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sms_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('price_per_sms')->default(25);
            $table->unsignedInteger('low_balance_threshold')->default(100);
            $table->string('sender_id')->nullable();
            $table->boolean('sending_enabled')->default(false);
            $table->timestamps();
        });

        Schema::create('sms_wallets', function (Blueprint $table): void {
            $table->id();
            $table->string('owner_type', 20)->index();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->unsignedInteger('credits_purchased')->default(0);
            $table->unsignedInteger('credits_used')->default(0);
            $table->integer('balance')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('sms_purchases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sms_wallet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('sms_quantity');
            $table->unsignedInteger('price_per_sms')->default(25);
            $table->unsignedInteger('total_amount');
            $table->string('status', 20)->default('pending')->index();
            $table->string('payment_reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sms_campaigns', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sms_wallet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sent_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('target_type', 40);
            $table->text('message');
            $table->unsignedInteger('recipients_count')->default(0);
            $table->unsignedInteger('sms_parts')->default(1);
            $table->unsignedInteger('total_credits_used')->default(0);
            $table->string('status', 20)->default('pending')->index();
            $table->json('beem_response')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sms_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sms_campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('visitor_id')->nullable()->constrained()->nullOnDelete();
            $table->string('recipient_name');
            $table->string('phone_number', 30);
            $table->text('message');
            $table->string('status', 20)->default('pending')->index();
            $table->string('beem_message_id')->nullable();
            $table->text('error_message')->nullable();
            $table->json('provider_response')->nullable();
            $table->timestamps();
        });

        Schema::create('sms_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sms_wallet_id')->constrained()->cascadeOnDelete();
            $table->string('transaction_type', 20)->index();
            $table->unsignedInteger('credits_in')->default(0);
            $table->unsignedInteger('credits_out')->default(0);
            $table->integer('balance_before');
            $table->integer('balance_after');
            $table->string('description')->nullable();
            $table->foreignId('performed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_transactions');
        Schema::dropIfExists('sms_logs');
        Schema::dropIfExists('sms_campaigns');
        Schema::dropIfExists('sms_purchases');
        Schema::dropIfExists('sms_wallets');
        Schema::dropIfExists('sms_settings');
    }
};
