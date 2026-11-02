<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Changes Infor made to the documents of a day (today or later), found when a
     * sync replaced the vendor's data: which parts changed and from what to what.
     */
    public function up(): void
    {
        Schema::create('document_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            // labels | di
            $table->string('document', 10);
            $table->date('date');
            // [{"part": "BDTS-28-911A", "before": 400, "after": 425}, ...]; null before = new, null after = removed
            $table->json('parts');
            $table->timestamp('detected_at');

            $table->index(['vendor_id', 'date', 'document']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_changes');
    }
};
