<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Who opened the day of a new document in the calendar, and when. No foreign keys:
     * SQL Server rejects several cascade paths and old changes are deleted by the sync.
     */
    public function up(): void
    {
        Schema::create('document_views', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('document_change_id');
            $table->timestamp('viewed_at');

            $table->unique(['user_id', 'document_change_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_views');
    }
};
