<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsTransferColumnToOperationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('operations', function (Blueprint $table) {
            $table->boolean('is_transfer')->default(0);
        });

        DB::table('operations')
            ->where('comment', 'LIKE', 'Перенос товара на склад%')
            ->orWhere('comment', 'LIKE', 'Перенос товара со склада%')
            ->update(['is_transfer' => 1]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('operations', function (Blueprint $table) {
            $table->dropColumn(['is_transfer']);
        });
    }
}
