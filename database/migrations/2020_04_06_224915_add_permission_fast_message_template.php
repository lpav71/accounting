<?php

use Spatie\Permission\Models\Permission;
use Illuminate\Database\Migrations\Migration;

class AddPermissionFastMessageTemplate extends Migration
{
    protected static $addPermissions = [
        'fast-message-template-list',
        'fast-message-template-create',
        'fast-message-template-edit',
        'fast-message-template-delete',
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
