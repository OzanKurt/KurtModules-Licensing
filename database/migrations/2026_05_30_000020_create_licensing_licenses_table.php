<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licensing_licenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('licensing_products')->restrictOnDelete();
            $table->string('key_hash')->unique();
            $table->string('key_prefix');
            $table->string('licensee_email');
            $table->foreignId('licensee_user_id')->nullable()
                ->constrained(config('auth.providers.users.table', 'users'))->nullOnDelete();
            $table->string('licensee_name')->nullable();
            $table->string('licensee_company')->nullable();
            $table->string('status')->default('active');
            $table->string('policy_type')->default('perpetual');
            $table->unsignedInteger('max_activations')->default(1);
            $table->timestamp('issued_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('updates_until')->nullable();
            $table->string('order_reference')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->string('revoked_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('status');
            $table->index('licensee_email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licensing_licenses');
    }
};
