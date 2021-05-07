<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenameCollectionsToCombinations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::rename('collections','combinations');
        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('collection_id', 'combination_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::rename('collections','combinations');
        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('combination_id', 'collection_id');
        });
    }
}
