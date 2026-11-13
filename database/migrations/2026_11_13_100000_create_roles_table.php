<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Roles and what each one is allowed to do. users.role keeps holding the role key
     * (no foreign key, like the other tables). The three roles that already existed are created
     * with what they could do until now, plus "Cadena de suministro" for Operaciones.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('key', 20)->unique();
            $table->string('name', 100);
            $table->text('permissions');
            $table->timestamps();
        });

        $all = array_keys(config('permissions'));
        $admin = ['activity.view', 'sync-runs.view', 'infor-data.view'];
        $portal = array_values(array_diff($all, $admin));

        foreach ([
            ['admin', 'Administrador', $all],
            // Planeación de proveedores also sees the activity of the vendors; Cadena de suministro, the syncs and the Infor data
            ['planner', 'Planeador', [...$portal, 'activity.view']],
            ['supply_chain', 'Cadena de suministro', [...$portal, 'sync-runs.view', 'infor-data.view']],
            ['supplier', 'Proveedor', array_values(array_diff($portal, ['vendors.all']))],
        ] as [$key, $name, $permissions]) {
            DB::table('roles')->insert([
                'key' => $key,
                'name' => $name,
                'permissions' => json_encode($permissions),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
