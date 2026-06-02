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
        Schema::create('site_visits', function (Blueprint $table): void {
            $table->id();
            $table->string('visitor_hash', 64)->index();
            $table->string('ip_hash', 64)->nullable()->index();
            $table->string('user_agent_hash', 64)->nullable();
            $table->string('route_name')->nullable()->index();
            $table->string('path')->index();
            $table->string('referrer_host')->nullable();
            $table->timestamp('visited_at')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_visits');
    }
};
