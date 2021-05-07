<?php

use Spatie\Permission\Models\Permission;
use Illuminate\Database\Migrations\Migration;

class DropOrderDeletePermission extends Migration
{
    protected static $deletePermissions = [
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
            Permission::destroy(Permission::all()->where('name', '=', $permission)->pluck('id'));
        }, self::$deletePermissions);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        array_map(function ($permission) {
            Permission::create(['name' => $permission]);
        }, self::$deletePermissions);
    }
}
