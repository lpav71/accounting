<?php

use Spatie\Permission\Models\Permission;
use Illuminate\Database\Migrations\Migration;

class AddPermissionsOrderState extends Migration
{
    protected static $addPermissions = [
        'orderState-list',
        'orderState-create',
        'orderState-edit',
        'orderState-delete',
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
