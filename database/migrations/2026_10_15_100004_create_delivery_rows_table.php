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
        // Delivery instruction rows: PO lines (HPO), firm planned orders (FP) and forecasts (FR)
        Schema::create('delivery_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('part_number_id')->constrained()->cascadeOnDelete();
            $table->string('origin', 2);
            $table->unsignedInteger('number')->nullable();
            $table->unsignedSmallInteger('line')->nullable();
            $table->decimal('quantity', 14, 3);
            $table->date('due_date');
            $table->timestamps();

            $table->index(['vendor_id', 'due_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_rows');
    }
};
