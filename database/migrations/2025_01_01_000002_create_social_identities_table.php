<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        Schema::connection('central')->create('social_identities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('provider');           // e.g. 'google'
            $table->string('provider_id');        // provider's user identifier (Google sub)
            $table->string('provider_email')->nullable();
            $table->text('access_token');         // encrypted via Eloquent cast
            $table->text('refresh_token')->nullable(); // encrypted; null if not granted
            $table->timestamp('token_expires_at')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('social_identities');
    }
};
