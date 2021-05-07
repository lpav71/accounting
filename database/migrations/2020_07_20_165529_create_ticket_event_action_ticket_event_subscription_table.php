<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTicketEventActionTicketEventSubscriptionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ticket_event_action_ticket_event_subscription', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('ticket_event_action_id');
            $table->unsignedInteger('ticket_event_subscription_id');
            $table->timestamps();

            $table->foreign('ticket_event_action_id','teates_tea_foreign')->references('id')->on('ticket_event_actions');
            $table->foreign('ticket_event_subscription_id','teates_tes_foreign')->references('id')->on('ticket_event_subscriptions');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ticket_event_action_ticket_event_subscription');
    }
}
