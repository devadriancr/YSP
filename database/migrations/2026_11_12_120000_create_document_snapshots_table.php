<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * What a vendor's labels and delivery instruction looked like after a sync that changed them,
     * so an old version can be shown and downloaded again. Each change points to its snapshot.
     */
    public function up(): void
    {
        Schema::create('document_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->timestamp('taken_at');
            // gzip + base64 of the JSON: { lines: [...], rows: [...] }
            $table->longText('payload');

            $table->index(['vendor_id', 'taken_at']);
        });

        Schema::table('document_changes', function (Blueprint $table) {
            // No foreign key: SQL Server rejects several cascade paths and old snapshots are deleted by the sync
            $table->unsignedBigInteger('snapshot_id')->nullable()->after('parts');
        });
    }

    public function down(): void
    {
        Schema::table('document_changes', function (Blueprint $table) {
            $table->dropColumn('snapshot_id');
        });
        Schema::dropIfExists('document_snapshots');
    }
};
