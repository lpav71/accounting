<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\User;

/**
 * Class ModelChange хранит изменения и состаяния моделей
 *
 * @package App
 * @property int $id
 * @property string $type
 * @property int|null $user_id
 * @property string $model_id
 * @property string $old_value
 * @property string $new_value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ModelChange newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ModelChange newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ModelChange query()
 * @mixin \Eloquent
 */
class ModelChange extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'type',
        'user_id',
        'model_id',
        'old_value',
        'new_value'
    ];

    /**
     *Возвращает изменений определённого класса. Второй параметр указывает наличие изменения какого поля необходимо.
     *
     * @param string $className
     * @param array|string|null $changedValues
     * @return \Illuminate\Database\Query\Builder
     */
    public static function getModelsChenges(string $className, $changedValues = null){
        $builder = ModelChange::where('type',$className)->orderBy('created_at','asc');
        if(!empty($changedValues)){
            $changedValues = is_array($changedValues) ? $changedValues : compact('changedValues');
            foreach($changedValues as $value){
                $builder->where('new_value','like','%'.$value.'%');
            }
        }
        return $builder;
    }

    /**
     *
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(){
        return $this->belongsTo(User::class);
    }

    /**
     * Значения модели до изменения
     *
     * @return \Illuminate\Support\Collection
     */
    public function old_values(){
        return collect(json_decode($this->old_value,true));
    }

    /**
     * Изменённые значения модели
     *
     * @return \Illuminate\Support\Collection
     */
    public function changed_values(){
        return collect(json_decode($this->new_value,true));
    }

    /**
     * Значения модели после изменения
     *
     * @return \Illuminate\Support\Collection
     */
    public function new_values(){
        return $this->old_values()->merge($this->changed_values());
    }

    /**
     * Массив полей которые были изменены
     *
     * @return array
     */
    public function changed_fields(){
        return $this->changed_values()->keys()->all();
    }

    /**
     * Проверка было ли поле изменено
     *
     * @param $fieldname
     * @return bool
     */
    public function is_field_changed($fieldname){
        if(array_key_exists($fieldname, $this->changed_values()->keys()->all())){
            return true;
        }
        return false;
    }

    /**
     * Возвращает оригинальную модель
     *
     * @return Model
     */
    public function old_model(){
        $model = $this->type::find($this->model_id);
        return $model->fill($this->old_values()->toArray());
    }

    /**
     * Возвращает изменённую модель
     *
     * @return mixed
     */
    public function new_model(){
        $model = $this->type::find($this->model_id);
        return $model->fill($this->new_values()->toArray());
    }
}
