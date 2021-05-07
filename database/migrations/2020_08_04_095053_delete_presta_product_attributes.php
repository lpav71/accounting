<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

class DeletePrestaProductAttributes extends Migration
{

    protected static $addPermissions = [
        'presta-product-attributes-list',
        'presta-product-attributes-create',
        'presta-product-attributes-edit',
        'presta-product-attributes-delete',
    ];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('presta_product_presta_product_attribute');

        array_map(function ($permission) {
            Permission::destroy(Permission::all()->where('name', '=', $permission)->pluck('id'));
        }, self::$addPermissions);

        Schema::dropIfExists('presta_product_attributes');



    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        Schema::create('presta_product_attributes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        array_map(function ($permission) {
            Permission::create(['name' => $permission]);
        }, self::$addPermissions);

        Schema::create('presta_product_presta_product_attribute', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('presta_product_id');
            $table->integer('presta_product_attribute_id');
            $table->string('attr_value')->nullable();
            $table->timestamps();
        });
    }
}
