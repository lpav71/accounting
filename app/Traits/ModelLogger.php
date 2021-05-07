<?php

namespace App\Traits;
use App\ModelChange;

/**
 * Trait ModelLogger Позволяет моделям сохранять историю изменений в App\ModelChange и получать список своих изменений
 *
 * @package App\Traits
 */
trait ModelLogger {

    /**
     * Сохраняет произведённые изменения
     */
    public function putChanges(){
        if(!empty($this->getDirty())){
            ModelChange::create([
                'type' => get_class($this),
                'user_id' => \Auth::id() ?? null,
                'model_id' => $this->id,
                'new_value'=> json_encode($this->getDirty(),JSON_UNESCAPED_UNICODE),
                'old_value'=> json_encode($this->getOriginal(),JSON_UNESCAPED_UNICODE)
            ]);
        }
    }

    /**
     * Список изменений модели
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function getChanges(){
        return $this->hasMany(ModelChange::class,'model_id','id')->where('type',get_class($this))->orderBy('created_at','ASC');
    }

}