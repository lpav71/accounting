<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTargetIdColumnToCallEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('call_events', function (Blueprint $table) {
            $table->string('targetId')->nullable();
            $table->string('phone')->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('call_events', function (Blueprint $table) {
            $table->dropColumn([
                'targetId',
            ]);
            $table->string('phone')->nullable(false)->default('')->change();
        });
    }
}
