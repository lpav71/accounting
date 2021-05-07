<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\NotificationTemplate
 *
 * @property int $id
 * @property int $order_state_id
 * @property int $channel_id
 * @property string|null $template
 * @property int $is_email
 * @property int $is_sms
 * @property int $is_disabled
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $carrier_type_id
 * @property-read \App\CarrierType $carrier_type
 * @property-read \App\Channel $channel
 * @property-read \App\OrderState $orderState
 * @method static \Illuminate\Database\Eloquent\Builder|\App\NotificationTemplate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\NotificationTemplate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\NotificationTemplate query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\NotificationTemplate whereCarrierTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\NotificationTemplate whereChannelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\NotificationTemplate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\NotificationTemplate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\NotificationTemplate whereIsDisabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\NotificationTemplate whereIsEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\NotificationTemplate whereIsSms($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\NotificationTemplate whereOrderStateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\NotificationTemplate whereTemplate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\NotificationTemplate whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property string|null $email_subject
 * @method static \Illuminate\Database\Eloquent\Builder|\App\NotificationTemplate whereEmailSubject($value)
 */
class NotificationTemplate extends Model
{


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function orderState(){
        return $this->belongsTo('App\OrderState');
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function channel(){
        return $this->belongsTo('App\Channel');
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function carrier_type(){
        return $this->belongsTo('App\CarrierType');
    }
}
