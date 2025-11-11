<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('gift_card_transactions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('gift_card_id')->constrained('gift_cards')->cascadeOnDelete();
            $t->enum('direction', ['issue','redeem','refund','adjust']);
            $t->unsignedBigInteger('amount_cents');
            $t->unsignedBigInteger('balance_before_cents')->nullable();
            $t->unsignedBigInteger('balance_after_cents')->nullable();
            $t->unsignedBigInteger('woocommerce_order_id')->nullable();
            $t->string('idempotency_key', 191)->nullable();
            $t->string('reference', 128)->nullable();
            $t->json('details')->nullable();
            $t->timestamps();

            $t->unique('idempotency_key');
            $t->index(['gift_card_id','created_at']);
            $t->index('reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gift_card_transactions');
    }
};
