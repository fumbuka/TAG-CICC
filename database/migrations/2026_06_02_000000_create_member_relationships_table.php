<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_relationships', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('related_member_id')->constrained('members')->cascadeOnDelete();
            $table->string('relationship_type');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['member_id', 'related_member_id', 'relationship_type'], 'member_relationship_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_relationships');
    }
};
