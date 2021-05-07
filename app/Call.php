<?php

namespace App;


use Illuminate\Database\Eloquent\Model;
use getID3;

/**
 * App\Call
 *
 * @property int $id
 * @property string $extTrackingId
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $recordUrl
 * @property int $number_attempt_request_record
 * @property int $length
 * @property int|null $is_recordable
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\CallEvent[] $events
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Call whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Call whereExtTrackingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Call whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Call whereIsRecordable($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Call whereNumberAttemptRequestRecord($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Call whereRecordUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Call whereUpdatedAt($value)
 * @mixin \Eloquent
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Call newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Call newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Call query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Call whereLength($value)
 * @property string $telephony_name
 */
class Call extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'extTrackingId',
        'recordUrl',
        'number_attempt_request_record',
        'is_recordable',
        'length',
        'telephony_name'
    ];

    /**
     * Get orders's Order Details
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function events()
    {
        return $this->hasMany('App\CallEvent','call_id','id');
    }



    /**
     * @return int
     * @throws \getid3_exception
     */
    public function callLength(){
        if($this->length!=null){
            return $this->length;
        }
        if($this->recordUrl=='' || $this->recordUrl==null){
            return 0;
        }
            $getID3 = new getID3;
            $fileInfo = $getID3->analyze(storage_path('app/').$this->recordUrl);
            try{
                $this->update(['length'=> (int) $fileInfo['playtime_seconds']]);
                return (int) $fileInfo['playtime_seconds'];
            }catch (\Exception $e){
                \Log::error($e->getMessage());
            }
        return 0;
    }
}
