<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('giftcard_transactions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('giftcard_id')->constrained('giftcards')->cascadeOnDelete();
            $t->enum('type', ['credit','debit','adjustment','webhook']);
            $t->unsignedBigInteger('amount');
            $t->string('reference', 128)->nullable();
            $t->json('details')->nullable();
            $t->timestamps();

            $t->index(['giftcard_id','created_at']);
            $t->index('reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('giftcard_transactions');
    }
};
