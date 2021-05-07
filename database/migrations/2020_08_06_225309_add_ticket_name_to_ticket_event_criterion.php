<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTicketNameToTicketEventCriterion extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ticket_event_criteria', function (Blueprint $table) {
            $table->string('ticket_name_substring')->nullable();
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
            $table->dropColumn('ticket_name_substring');
        });
    }
}
