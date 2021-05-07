<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTicketThemesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ticket_themes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->unsignedInteger('ticket_theme_id')->after('ticket_priority_id');
            $table->foreign('ticket_theme_id')->references('id')->on('ticket_themes');
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
            $table->dropForeign('tickets_ticket_theme_id_foreign');
            $table->dropColumn('ticket_theme_id');
        });
        Schema::dropIfExists('ticket_themes');
    }
}
