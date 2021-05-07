<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTicketEventActionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ticket_event_actions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->unsignedInteger('add_user_id')->nullable();
            $table->string('auto_message')->nullable();
            $table->unsignedInteger('ticket_priority_id')->nullable();
            $table->unsignedInteger('performer_user_id')->nullable();
            $table->string('message_replace')->nullable();
            $table->enum('notify',['CREATOR','PERFORMER','ALL'])->nullable();
            $table->timestamps();

            $table->foreign('add_user_id')->references('id')->on('users');
            $table->foreign('ticket_priority_id')->references('id')->on('ticket_priorities');
            $table->foreign('performer_user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ticket_event_actions', function (Blueprint $table) {
            $table->dropForeign('ticket_event_actions_add_user_id_foreign');
            $table->dropForeign('ticket_event_actions_performer_user_id_foreign');
            $table->dropForeign('ticket_event_actions_ticket_priority_id_foreign');
        });
        Schema::dropIfExists('ticket_event_actions');
    }
}
