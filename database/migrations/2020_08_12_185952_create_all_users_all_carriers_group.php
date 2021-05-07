<?php

use App\Carrier;
use App\CarrierGroup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAllUsersAllCarriersGroup extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::transaction(function () {
            $carrierGroup = CarrierGroup::create(['id' => 1, 'name' => 'all_users']);
            $carrierGroup->save();
            $carrierGroup->users()->sync(\App\User::all()->pluck('id'));
            $carrierGroup->carriers()->saveMany(Carrier::all());
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $carrierGroup = CarrierGroup::find(1);
        foreach ($carrierGroup->carriers as $carrier) {
            $carrier->update([
                'carrier_group_id' => null
            ]);
        }

        $carrierGroup->users()->detach();

        $carrierGroup->delete();
    }
}
