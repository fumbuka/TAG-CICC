<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_income_category', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('income_category_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['department_id', 'income_category_id'], 'department_income_category_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_income_category');
    }
};
