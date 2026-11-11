<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // What Infor changed in the unreceived lines between two syncs: a line that appeared,
        // one that is gone (received in full or deleted) and one whose pending quantity changed
        Schema::create('unreceived_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('number');
            $table->unsignedSmallInteger('line');
            $table->string('part', 35);
            $table->string('kind', 10); // new, changed or removed
            $table->decimal('before', 14, 3)->nullable();
            $table->decimal('after', 14, 3)->nullable();
            $table->timestamp('detected_at');

            $table->index(['vendor_id', 'detected_at']);
        });

        // Set when the sync sent the unreceived list: without it there is nothing to compare with
        Schema::table('vendors', function (Blueprint $table) {
            $table->timestamp('unreceived_synced_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('vendors', fn (Blueprint $table) => $table->dropColumn('unreceived_synced_at'));
        Schema::dropIfExists('unreceived_changes');
    }
};
