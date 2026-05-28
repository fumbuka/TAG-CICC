<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('zone_id')->nullable()->constrained()->nullOnDelete();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('gender', 20);
            $table->date('date_of_birth')->nullable();
            $table->string('phone_number', 20)->nullable()->index();
            $table->string('alternative_phone_number', 20)->nullable();
            $table->string('email')->nullable()->index();
            $table->string('residential_area')->nullable();
            $table->string('marital_status', 30)->nullable();
            $table->string('baptism_status', 30)->nullable();
            $table->string('membership_status', 30)->default('active')->index();
            $table->date('joined_at')->nullable();
            $table->string('source', 50)->default('member');
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone', 20)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
