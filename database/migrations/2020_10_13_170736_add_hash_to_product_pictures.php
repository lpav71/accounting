<?php

use App\ProductPicture;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHashToProductPictures extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('product_pictures', function (Blueprint $table) {
            $table->string('hash')->default('');
        });
        $pictures = ProductPicture::all();
        $pictures->map(function (ProductPicture $productPicture) {
            try {
                $productPicture->getHash();
            }catch (\Exception $e){

            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_pictures', function (Blueprint $table) {
            $table->dropColumn(['hash']);
        });
    }
}
