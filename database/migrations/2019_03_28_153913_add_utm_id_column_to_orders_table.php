<?php

use App\Order;
use App\Utm;
use App\UtmCampaign;
use App\UtmSource;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUtmIdColumnToOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(
            'orders',
            function (Blueprint $table) {
                $table->integer('utm_id')->unsigned()->nullable()->after('gaClientID');
            }
        );

        DB::table('orders')
            ->select(['id', 'utm_campaign', 'utm_source'])
            ->get()
            ->each(
                function (stdClass $row) {
                    $utm = Utm::firstOrCreate(
                        [
                            'utm_campaign_id' => (UtmCampaign::firstOrCreate(['name' => $row->utm_campaign]))->id,
                            'utm_source_id' => (UtmSource::firstOrCreate(['name' => $row->utm_source]))->id,
                        ]
                    );

                    Order::find($row->id)->update(['utm_id' => $utm->id]);
                }
            );

        Schema::table(
            'orders',
            function (Blueprint $table) {
                $table->dropColumn(['utm_campaign', 'utm_source']);
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
            'orders',
            function (Blueprint $table) {
                $table->string('utm_campaign')->nullable()->after('utm_id');
                $table->string('utm_source')->nullable()->after('utm_campaign');
            }
        );

        Order::all()->each(
            function (Order $order) {
                DB::table('orders')
                    ->where('id', $order->id)
                    ->update(
                        [
                            'utm_campaign' => $order->utm_campaign,
                            'utm_source' => $order->utm_source,
                        ]
                    );
            }
        );


        Schema::table(
            'orders',
            function (Blueprint $table) {
                $table->dropColumn(['utm_id']);
            }
        );
    }
}
