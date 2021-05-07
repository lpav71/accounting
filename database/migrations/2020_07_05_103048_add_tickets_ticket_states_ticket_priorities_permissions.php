<?php

use Spatie\Permission\Models\Permission;
use Illuminate\Database\Migrations\Migration;

class AddTicketsTicketStatesTicketPrioritiesPermissions extends Migration
{
    protected static $addPermissions = [
        'ticket-list',
        'ticket-create',
        'ticket-edit',
        'ticket-delete',

        'ticketState-list',
        'ticketState-create',
        'ticketState-edit',
        'ticketState-delete',

        'ticketPriority-list',
        'ticketPriority-create',
        'ticketPriority-edit',
        'ticketPriority-delete',

        'ticketTheme-list',
        'ticketTheme-create',
        'ticketTheme-edit',
        'ticketTheme-delete',
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
