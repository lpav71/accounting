<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

class AddPermissionsForStoresAutotransfers extends Migration
{
    protected static $addPermissions = [
        'store-autotransfer-setting-list',
        'store-autotransfer-setting-create',
        'store-autotransfer-setting-process',
        'store-autotransfer-setting-edit',
        'store-autotransfer-setting-delete'
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
