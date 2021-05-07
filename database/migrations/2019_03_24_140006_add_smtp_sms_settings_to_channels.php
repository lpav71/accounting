
<?php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
class AddSmtpSmsSettingsToChannels extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->string("smtp_host")->nullable();
            $table->string("smtp_port")->nullable();
            $table->string("smtp_encryption")->nullable();
            $table->string("smtp_username")->nullable();
            $table->string("smtp_password")->nullable();
            $table->boolean("smtp_is_enabled")->default(0);
            $table->text('sms_template')->nullable();
            $table->boolean("sms_is_enabled")->default(0);
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
             $table->dropColumn([
                'smtp_host',
                'smtp_port',
                'smtp_encryption',
                'smtp_username',
                'smtp_password',
                'smtp_is_enabled',
                'sms_template',
                'sms_is_enabled'
             ]);
        });
    }
}