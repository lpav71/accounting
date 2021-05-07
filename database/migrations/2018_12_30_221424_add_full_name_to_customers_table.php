<?php

use App\Customer;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFullNameToCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('full_name')->after('last_name');
        });

        Customer::all()->each(function (Customer $customer) {
            $customer->update([
                'full_name' => $customer->first_name
                    . (is_null($customer->last_name) ? '' : ' ' . $customer->last_name)
                    . (is_null($customer->phone) ? '' : ' (' . $customer->phone . ')')
                    . (is_null($customer->email) ? '' : ' <' . $customer->email . '>')
            ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'full_name',
            ]);
        });
    }
}
