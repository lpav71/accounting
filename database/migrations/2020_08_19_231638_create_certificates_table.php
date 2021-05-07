<?php

use App\Category;
use App\Manufacturer;
use App\Product;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCertificatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->increments('id');
            $table->string('number')->unique()->nullable(true);
            $table->unsignedInteger('order_detail_id')->nullable(true);
            $table->timestamps();

            $table->foreign('order_detail_id')->references('id')->on('order_details');
        });

        Category::create([
            'name' => "Сертификаты",
            'category_id' => Category::where(['is_default' => 1])->first()->id,
            'is_certificate' => 1
        ]);

        Product::create([
            'title' => 'Сертификат',
            'name' => 'Сертификат',
            'reference' => 'bt_certificate',
            'manufacturer_id' => Manufacturer::first()->id,
            'category_id' => Category::where(['is_certificate' => 1])->first()->id,


        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropForeign('certificates_order_detail_id_foreign');
            $table->dropColumn([
                'order_detail_id'
            ]);
        });
        Schema::dropIfExists('certificates');
    }
}
