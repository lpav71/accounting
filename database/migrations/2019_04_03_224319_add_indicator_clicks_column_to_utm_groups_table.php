<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndicatorClicksColumnToUtmGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(
            'utm_groups',
            function (Blueprint $table) {
                $table->integer('indicator_clicks')->nullable();
            }
        );

        DB::table('utm_groups')
            ->select()
            ->get()
            ->each(
                function (stdClass $row) {
                    if (!is_null($row->indicator_clicks_from) && !is_null($row->indicator_clicks_to)) {
                        DB::table('utm_groups')
                            ->where('id', $row->id)
                            ->update(
                                [
                                    'indicator_clicks' => floor(($row->indicator_clicks_from + $row->indicator_clicks_to) / 2),
                                ]
                            );
                    }
                }
            );

        Schema::table(
            'utm_groups',
            function (Blueprint $table) {
                $table->dropColumn(
                    [
                        'indicator_clicks_to',
                        'indicator_clicks_from',
                    ]
                );
            }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(
            'utm_groups',
            function (Blueprint $table) {
                $table->dropColumn(['indicator_clicks']);
                $table->integer('indicator_clicks_from')->nullable();
                $table->integer('indicator_clicks_to')->nullable();
            }
        );
    }
}
