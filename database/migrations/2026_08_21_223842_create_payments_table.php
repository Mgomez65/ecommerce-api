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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            $table->string('provider')->default('mercadopago');
            $table->string('preference_id')->nullable();
            $table->string('external_id')->nullable();
            $table->string('status')->default('pending');
            $table->string('status_detail')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 10)->default('ARS');
            $table->json('raw_payload')->nullable();
            $table->timestamp('processed_at')->nullable();

            $table->timestamps();

            $table->unique(['provider', 'external_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
