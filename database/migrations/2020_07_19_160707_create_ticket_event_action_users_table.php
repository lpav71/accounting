<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTicketEventActionUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ticket_event_action_user', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('ticket_event_action_id');
            $table->unsignedInteger('user_id');
            $table->timestamps();


            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('ticket_event_action_id')->references('id')->on('ticket_event_actions');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ticket_event_action_user', function (Blueprint $table) {
            $table->dropForeign('ticket_event_action_user_user_id_foreign');
            $table->dropForeign('ticket_event_action_user_ticket_event_action_id_foreign');
        });

        Schema::dropIfExists('ticket_event_action_user');
    }
}
