<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTicketEventCriterionWeekdayTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ticket_event_criterion_weekday', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('ticket_event_criterion_id');
            $table->unsignedInteger('weekday_id');
            $table->timestamps();

            $table->foreign('ticket_event_criterion_id')->references('id')->on('ticket_event_criteria');
            $table->foreign('weekday_id')->references('id')->on('weekdays');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ticket_event_criterion_weekday', function (Blueprint $table) {
            $table->dropForeign('ticket_event_criterion_weekday_ticket_event_criterion_id_foreign');
            $table->dropForeign('ticket_event_criterion_weekday_weekday_id_foreign');
        });

        Schema::dropIfExists('ticket_event_criterion_weekday');
    }
}
