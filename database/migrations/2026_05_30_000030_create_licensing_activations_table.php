<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licensing_activations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_id')->constrained('licensing_licenses')->cascadeOnDelete();
            $table->string('fingerprint_hash');
            $table->string('label')->nullable();
            $table->string('ip')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('activated_at');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            $table->timestamps();
            $table->unique(['license_id', 'fingerprint_hash']);
            $table->index(['license_id', 'deactivated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licensing_activations');
    }
};
