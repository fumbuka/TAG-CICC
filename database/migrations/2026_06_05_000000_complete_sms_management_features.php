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
        Schema::create('sms_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('message');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::table('sms_campaigns', function (Blueprint $table): void {
            $table->foreignId('sms_template_id')->nullable()->after('department_id')->constrained('sms_templates')->nullOnDelete();
            $table->foreignId('scheduled_by_user_id')->nullable()->after('sent_by_user_id')->constrained('users')->nullOnDelete();
            $table->boolean('personalization_enabled')->default(false)->after('message');
            $table->timestamp('scheduled_at')->nullable()->after('beem_response')->index();
            $table->timestamp('last_attempted_at')->nullable()->after('scheduled_at');
        });

        Schema::table('sms_logs', function (Blueprint $table): void {
            $table->string('provider_status')->nullable()->after('status')->index();
            $table->timestamp('provider_status_updated_at')->nullable()->after('provider_status');
            $table->timestamp('delivered_at')->nullable()->after('provider_status_updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sms_logs', function (Blueprint $table): void {
            $table->dropColumn(['provider_status', 'provider_status_updated_at', 'delivered_at']);
        });

        Schema::table('sms_campaigns', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('sms_template_id');
            $table->dropConstrainedForeignId('scheduled_by_user_id');
            $table->dropColumn(['personalization_enabled', 'scheduled_at', 'last_attempted_at']);
        });

        Schema::dropIfExists('sms_templates');
    }
};
