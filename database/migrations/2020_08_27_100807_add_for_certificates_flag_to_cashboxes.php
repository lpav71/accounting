<?php

use App\Cashbox;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForCertificatesFlagToCashboxes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cashboxes', function (Blueprint $table) {
            $table->boolean('for_certificates')->default(0);
        });

        Cashbox::create([
            'name' => 'Сертификаты',
            'for_certificates' => 1,
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $cashbox = Cashbox::where(['name' => 'Сертификаты'])->where(['for_certificates' => 1])->first();
        $cashbox->delete();

        Schema::table('cashboxes', function (Blueprint $table) {
            $table->dropColumn(['for_certificates']);
        });
    }
}
