<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('converted_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->string('full_name');
            $table->string('phone_number', 20)->nullable();
            $table->string('residential_area')->nullable();
            $table->date('visited_at');
            $table->string('invited_by')->nullable();
            $table->string('follow_up_status', 30)->default('new');
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitors');
    }
};
