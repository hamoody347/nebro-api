<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->morphs('payer');
            $table->string('gateway');                   // e.g. 'stripe'
            $table->unsignedBigInteger('amount');        // cents
            $table->char('currency', 3);                 // ISO 4217, e.g. 'USD'
            $table->string('status');                    // pending|succeeded|failed|refunded
            $table->string('gateway_payment_id')->nullable()->index();
            $table->string('gateway_payment_intent_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
