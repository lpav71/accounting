<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketPrioritiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ticket_priorities', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->unique();
            $table->unsignedInteger('rate')->unique();
            $table->timestamps();
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->unsignedInteger('ticket_priority_id')->after('performer_user_id');
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
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign('tickets_ticket_priority_id_foreign');
            $table->dropColumn('ticket_priority_id');
        });
        Schema::dropIfExists('ticket_priorities');
    }
}
