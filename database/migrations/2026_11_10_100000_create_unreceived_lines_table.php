<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Overdue PO lines not fully received (HPO): YPU705B parts and YPU715B raw material
        Schema::create('unreceived_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            // no action: SQL Server rejects two cascade paths from vendors; the sync deletes the rows before their parts
            $table->foreignId('part_number_id')->constrained()->noActionOnDelete();
            $table->unsignedInteger('number');
            $table->unsignedSmallInteger('line');
            $table->string('item_class', 2);
            $table->decimal('quantity', 14, 3);
            $table->decimal('received_quantity', 14, 3)->default(0);
            $table->date('due_date');
            // Raw material only
            $table->string('material', 10)->nullable();
            $table->string('symbol', 20)->nullable();
            $table->decimal('thickness', 7, 2)->default(0);
            $table->decimal('width', 8, 1)->default(0);
            $table->decimal('length', 8, 1)->default(0);
            $table->timestamps();

            $table->index(['vendor_id', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unreceived_lines');
    }
};
