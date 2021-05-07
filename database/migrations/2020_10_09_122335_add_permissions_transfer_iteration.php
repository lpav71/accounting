<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

class AddPermissionsTransferIteration extends Migration
{
    protected static $addPermissions = [
        'transfer-iterations-list',
        'transfer-iterations-process',
        'transfer-iteration-create'
    ];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        array_map(function ($permission) {
            Permission::create(['name' => $permission]);
        }, self::$addPermissions);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        array_map(function ($permission) {
            Permission::destroy(Permission::all()->where('name', '=', $permission)->pluck('id'));
        }, self::$addPermissions);
    }
}
