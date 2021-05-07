<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketStateTicketState extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ticket_state_ticket_state', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('ticket_state_id');
            $table->unsignedInteger('next_ticket_state_id');
            $table->timestamps();

            $table->foreign('ticket_state_id')->references('id')->on('ticket_states');
            $table->foreign('next_ticket_state_id')->references('id')->on('ticket_states');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ticket_state_ticket_state', function (Blueprint $table) {
            $table->dropForeign('ticket_state_ticket_state_ticket_state_id_foreign');
            $table->dropForeign('ticket_state_ticket_state_next_ticket_state_id_foreign');
        });

        Schema::dropIfExists('ticket_state_ticket_state');
    }
}
