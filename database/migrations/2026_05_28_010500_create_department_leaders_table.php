<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_leaders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_position_id')->constrained()->restrictOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->date('started_at')->nullable();
            $table->date('ended_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['department_id', 'department_position_id', 'member_id', 'started_at'], 'department_leader_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_leaders');
    }
};
