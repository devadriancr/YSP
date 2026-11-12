<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unreceived_lines', function (Blueprint $table) {
            // Infor PID: only 'PO' lines are printed by YPU705B/715B; null = synced before this column
            $table->string('order_type', 2)->nullable()->after('line');
        });
    }

    public function down(): void
    {
        Schema::table('unreceived_lines', function (Blueprint $table) {
            $table->dropColumn('order_type');
        });
    }
};
