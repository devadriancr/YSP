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
        // Copy of AVM, only the fields the portal shows or prints
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('number')->unique();
            $table->string('name', 50);
            $table->string('short_name', 10)->nullable();
            $table->string('street', 50)->nullable();
            $table->string('locality', 50)->nullable();
            $table->string('region', 50)->nullable();
            $table->string('state', 3)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('country', 4)->nullable();
            $table->string('tax_id', 16)->nullable();
            $table->string('phone', 25)->nullable();
            $table->string('contact_name', 30)->nullable();
            $table->string('email', 50)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
