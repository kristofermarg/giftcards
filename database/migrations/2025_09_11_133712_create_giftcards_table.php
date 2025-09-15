<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('giftcards', function (Blueprint $t) {
            $t->id();
            $t->uuid('public_id')->unique();
            $t->string('code', 36)->unique();
            $t->unsignedBigInteger('initial_amount');
            $t->unsignedBigInteger('balance');
            $t->char('currency', 3)->default('ISK');
            $t->enum('status', ['active','inactive','redeemed','expired'])->default('active');
            $t->date('expires_at')->nullable();
            $t->json('meta')->nullable();
            $t->string('passkit_program_id', 64)->nullable();
            $t->string('passkit_member_id', 64)->nullable();
            $t->timestamps();
            $t->softDeletes();

            $t->index(['status','expires_at']);
            $t->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('giftcards');
    }
};
