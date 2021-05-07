<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTicketEventCriteriaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ticket_event_criteria', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            // текст добавленного сообщения содержит "text"
            $table->string('message_substring')->nullable();
            $table->unsignedInteger('ticket_theme_id')->nullable();
            $table->unsignedInteger('creator_user_id')->nullable();
            $table->unsignedInteger('performer_user_id')->nullable();
            $table->unsignedInteger('ticket_priority_id')->nullable();
            $table->enum('last_writer',['CREATOR','PERFORMER','OTHER'])->nullable();
            $table->timestamps();

            $table->foreign('ticket_theme_id')->references('id')->on('ticket_themes');
            $table->foreign('creator_user_id')->references('id')->on('users');
            $table->foreign('performer_user_id')->references('id')->on('users');
            $table->foreign('ticket_priority_id')->references('id')->on('ticket_priorities');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ticket_event_criteria', function (Blueprint $table) {
            $table->dropForeign('ticket_event_criteria_ticket_theme_id_foreign');
            $table->dropForeign('ticket_event_criteria_creator_user_id_foreign');
            $table->dropForeign('ticket_event_criteria_performer_user_id_foreign');
            $table->dropForeign('ticket_event_criteria_ticket_priority_id_foreign');
        });
        Schema::dropIfExists('ticket_event_criteria');
    }
}
