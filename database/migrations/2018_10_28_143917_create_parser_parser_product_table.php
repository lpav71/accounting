<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateParserParserProductTable extends Migration
{
    /**
     * Запуск миграции
     *
     * @return void
     */
    public function up()
    {
        Schema::create('parser_parser_product', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('parser_id')->unsigned();
            $table->integer('parser_product_id')->unsigned();
            $table->float('price');
            $table->string('link');
            $table->timestamps();
        });
    }

    /**
     * Откат миграции
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('parser_parser_product');
    }
}
