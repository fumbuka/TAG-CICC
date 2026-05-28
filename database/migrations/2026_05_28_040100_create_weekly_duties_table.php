<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weekly_duties', function (Blueprint $table) {
            $table->id();
            $table->date('week_start')->index();
            $table->date('week_end')->index();
            $table->foreignId('elder_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->foreignId('deacon_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_duties');
    }
};
