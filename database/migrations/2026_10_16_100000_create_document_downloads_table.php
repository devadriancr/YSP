<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every download of identification cards or a delivery instruction from the calendar,
     * with a fingerprint of the data it had: when Infor changes that data later, the
     * calendar can tell that the downloaded copy is out of date.
     */
    public function up(): void
    {
        Schema::create('document_downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            // Kept when the user is deleted, so the history stays
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_name');
            // Role at the time of the download: only suppliers' downloads are shown in the
            // calendar; planners and admins download as often as they need without marking it
            $table->string('user_role', 20);
            // labels | di
            $table->string('document', 10);
            $table->date('date');
            $table->char('fingerprint', 64);
            $table->timestamp('downloaded_at');

            $table->index(['vendor_id', 'user_role', 'date', 'document']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_downloads');
    }
};
