<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddYaReferenceCompaniesColumnsToChannels extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->string('ya_ad_type');            
            $table->string('ya_phrase');
            $table->string('ya_header_1');
            $table->string('ya_header_2');
            $table->string('ya_text');
            $table->string('ya_link_text');
            $table->string('ya_region');
            $table->string('ya_bet');
            $table->string('ya_quick_link');
            $table->string('ya_quick_link_addr');
            $table->string('ya_details');
            $table->string('ya_endpoint');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->dropColumn(['ya_endpoint','ya_ad_type','ya_phrase','ya_header_1','ya_header_2','ya_text','ya_link_text','ya_region','ya_bet','ya_quick_link','ya_quick_link_addr','ya_details']);
        });
    }
}
