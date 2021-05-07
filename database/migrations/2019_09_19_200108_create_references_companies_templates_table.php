<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReferencesCompaniesTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('references_companies_templates', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('ad_type');
            $table->string('phrase');
            $table->string('header_1');
            $table->string('header_2');
            $table->text('text');
            $table->text('link_text');
            $table->string('region');
            $table->string('bet');
            $table->text('quick_link');
            $table->text('quick_link_addr');
            $table->text('details');
            $table->text('quick_link_descr');
            $table->text('link');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('references_companies_templates');
    }
}
