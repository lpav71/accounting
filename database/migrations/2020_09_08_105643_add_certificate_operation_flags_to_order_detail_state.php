<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCertificateOperationFlagsToOrderDetailState extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_detail_states', function (Blueprint $table) {
            $table->boolean('crediting_certificate')->default(0);
            $table->boolean('writing_off_certificate')->default(0);
            $table->boolean('zeroing_certificate_number')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_detail_states', function (Blueprint $table) {
            $table->dropColumn(['crediting_certificate']);
            $table->dropColumn(['writing_off_certificate']);
            $table->dropColumn(['zeroing_certificate_number']);
        });
    }
}
