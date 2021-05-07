<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTicketEventCriterionTicketEventSubscriptionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ticket_event_criterion_ticket_event_subscription', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('ticket_event_criterion_id');
            $table->unsignedInteger('ticket_event_subscription_id');
            $table->timestamps();

            $table->foreign('ticket_event_criterion_id','tectes_tec_foreign')->references('id')->on('ticket_event_criteria');
            $table->foreign('ticket_event_subscription_id','tectes_tes_foreign')->references('id')->on('ticket_event_subscriptions');
        });

        }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ticket_event_criterion_ticket_event_subscription');
    }
}
