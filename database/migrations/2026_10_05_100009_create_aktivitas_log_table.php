<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aktivitas_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->string('aktivitas');
            $table->string('modul', 50);
            $table->string('subjek_type', 100)->nullable();
            $table->unsignedBigInteger('subjek_id')->nullable();
            $table->json('data')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('modul');
            $table->index('created_at');
            $table->index(['subjek_type', 'subjek_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aktivitas_log');
    }
};
