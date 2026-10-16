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
        // Work calendar (FSC): one character per day of the year
        Schema::create('work_calendars', function (Blueprint $table) {
            $table->id();
            $table->string('facility', 3);
            $table->unsignedInteger('work_center');
            $table->unsignedSmallInteger('year');
            $table->char('days', 366);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['facility', 'work_center', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_calendars');
    }
};
