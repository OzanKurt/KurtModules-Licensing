<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licensing_license_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_id')->constrained('licensing_licenses')->cascadeOnDelete();
            $table->string('action');
            $table->json('context')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licensing_license_events');
    }
};
