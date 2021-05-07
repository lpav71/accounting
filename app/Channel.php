<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Источник заказа
 *
 * @author Vladimir Tikunov <vtikunov@yandex.ru>
 * @property int $id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $invoice_template
 * @property string|null $cheque_template
 * @property string|null $guarantee_template
 * @property string|null $call_target_id
 * @property string|null $call_subscription_id
 * @property string|null $go_proxy_url
 * @property string|null $yandex_token
 * @property string|null $yandex_counter
 * @property string|null $google_counter
 * @property string|null $smtp_host
 * @property string|null $smtp_port
 * @property string|null $smtp_encryption
 * @property string|null $smtp_username
 * @property string|null $smtp_password
 * @property int $smtp_is_enabled
 * @property string|null $sms_template
 * @property int $sms_is_enabled
 * @property string|null $phone
 * @property string|null $template_name
 * @property string|null $notifications_email
 * @property string|null $upload_address
 * @property string|null $upload_key
 * @property string $ya_ad_type
 * @property string $ya_phrase
 * @property string $ya_header_1
 * @property string $ya_header_2
 * @property string $ya_text
 * @property string $ya_link_text
 * @property string $ya_region
 * @property string $ya_bet
 * @property string $ya_endpoint
 * @property string $ya_quick_link
 * @property string $ya_quick_link_addr
 * @property string $db_name
 * @property string $ya_details
 * @property string $url
 * @property string $ya_quick_link_descr
 * @property int $is_landscape_docs
 * @property string|null $courier_template
 * @property int $is_hidden
 * @property string $download_address
 * @property string $check_certificate_token
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\NotificationTemplate[] $notificationTemplates
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Channel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Channel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Channel query()
 * @mixin \Eloquent
 * @property string $telephony_numbers
 * @property string $messenger_settings
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ExpenseSettings[] $expenseSettings
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\FastMessageTemplate[] $fastMessageTemplates
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\User[] $manager
 * @property string $check_title
 * @property string $check_user
 * @property string $inn
 * @property string $ogrn
 * @property string $kpp
 * @property string $check_place
 * @property string $check_qr_code
 * @property string $check_title_2
 * @property int $is_new_upload_api
 * @property string|null $upload_address_price
 * @property string|null $upload_address_availability
 */
class Channel extends Model
{
    protected $fillable = [
        'name',
        'invoice_template',
        'cheque_template',
        'guarantee_template',
        'courier_template',
        'call_target_id',
        'call_subscription_id',
        'go_proxy_url',
        'yandex_token',
        'yandex_counter',
        'google_counter',
        'smtp_host',
        'smtp_port',
        'smtp_encryption',
        'smtp_username',
        'smtp_password',
        'smtp_is_enabled',
        'sms_template',
        'sms_is_enabled',
        'phone',
        'template_name',
        'notifications_email',
        'upload_address',
        'download_address',
        'upload_key',
        'ya_ad_type',
        'ya_phrase',
        'ya_header_1',
        'ya_header_2',
        'ya_text',
        'ya_link_text',
        'ya_region',
        'ya_bet',
        'ya_quick_link',
        'ya_quick_link_descr',
        'ya_quick_link_addr',
        'ya_details',
        'ya_endpoint',
        'url',
        'is_landscape_docs',
        'is_hidden',
        'telephony_numbers',
        'messenger_settings',
        'db_name',
        'check_certificate_token',
        'db_name',
        'check_title',
        'check_title_2',
        'check_user',
        'inn',
        'ogrn',
        'kpp',
        'check_place',
        'check_qr_code',
        'upload_address_price',
        'upload_address_availability'
    ];

    /**
     * Шаблоны оповещений
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function notificationTemplates()
    {
        return $this->hasMany(NotificationTemplate::class);
    }

    public static function whereContentControl()
    {
        return self::whereNotNull('upload_key');
    }

    /**
     * fast message templates
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function fastMessageTemplates()
    {
        return $this->belongsToMany(FastMessageTemplate::class);
    }

    /**
     * Настройки в которых используются источник
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function expenseSettings()
    {
        return $this->belongsToMany(ExpenseSettings::class, 'channel_expense_setting');
    }
    
    /*
     * Менеджеры работающие над магазином
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function manager()
    {
        return $this->belongsToMany(User::class, 'channels_managers', 'channel_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }
}
