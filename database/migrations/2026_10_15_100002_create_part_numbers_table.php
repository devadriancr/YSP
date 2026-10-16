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
        // Parts of each vendor (IIM + IIU), with the next process already resolved (FRT + LWK)
        Schema::create('part_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->string('number', 35);
            $table->string('description', 50)->nullable();
            $table->string('project')->nullable();
            $table->string('standard_pack')->nullable();
            $table->unsignedInteger('standard_pack_quantity')->default(0);
            $table->string('side')->nullable();
            $table->string('next_process', 30)->nullable();
            $table->timestamps();

            $table->unique(['vendor_id', 'number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('part_numbers');
    }
};
