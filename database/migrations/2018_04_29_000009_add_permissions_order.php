<?php

use Spatie\Permission\Models\Permission;
use Illuminate\Database\Migrations\Migration;

class AddPermissionsOrder extends Migration
{
    protected static $addPermissions = [
        'order-list',
        'order-create',
        'order-edit',
        'order-delete',
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
