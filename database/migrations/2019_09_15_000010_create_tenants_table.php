<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTenantsTable extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        Schema::connection('central')->create('tenants', function (Blueprint $table) {
            $table->string('id')->primary(); // slug-style ID, e.g. "acme-corp"
            $table->string('name');
            $table->string('plan')->nullable();
            $table->timestamps();
            $table->json('data')->nullable(); // stancl virtual columns storage
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('tenants');
    }
}
