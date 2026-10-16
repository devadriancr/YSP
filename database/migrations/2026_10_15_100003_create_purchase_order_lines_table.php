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
        // Open PO lines (HPO), one identification card per box
        Schema::create('purchase_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('part_number_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('number');
            $table->unsignedSmallInteger('line');
            $table->decimal('quantity', 14, 3);
            $table->date('due_date');
            $table->string('shift', 1)->nullable();
            $table->string('warehouse', 3)->nullable();
            $table->timestamps();

            $table->index(['vendor_id', 'due_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_lines');
    }
};
