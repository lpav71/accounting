<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ExpenseSettings
 *
 * @package App
 * @property string $name
 * @property string $summ
 * @property integer $utm_campaign_id
 * @property integer $category_id
 * @property int $expense_category_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @mixin \Eloquent
 * @property int $id
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Carrier[] $carriers
 * @property-read \App\Category $category
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Channel[] $channels
 * @property-read \App\ExpenseCategory|null $expenseCategory
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Manufacturer[] $manufacturters
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\OrderState[] $orderStates
 * @property-read \App\UtmCampaign $utmCampaign
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ExpenseSettings newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ExpenseSettings newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ExpenseSettings query()
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\CarrierGroup[] $carrierGroups
 */
class ExpenseSettings extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'summ',
        'utm_campaign_id',
        'category_id',
        'expense_category_id'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Бренды для которых используется настройка
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function manufacturters()
    {
        return $this->belongsToMany(Manufacturer::class, 'expense_settings_brands_expense_settings', 'setting_id', 'brand_id')->withTimestamps();
    }

    /**
     * Службы доставки для которых используется настройка
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function carriers()
    {
        return $this->belongsToMany(Carrier::class, 'expense_settings_carriers_expense_settings', 'setting_id', 'carrier_id')->withTimestamps();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function utmCampaign()
    {
        return $this->belongsTo(UtmCampaign::class);
    }

    /**
     * Статусы заказов для которых ипользуется расход
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function orderStates()
    {
        return $this->belongsToMany(OrderState::class, 'expense_settings_order_state_expense_settings', 'setting_id', 'order_state_id')->withTimestamps();
    }

    /**
     * Источники заказов для которых используется расход
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function channels()
    {
        return $this->belongsToMany(Channel::class, 'channel_expense_setting')->withTimestamps();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function expenseCategory()
    {
        return $this->belongsTo(ExpenseCategory::class);
    }

    /**
     * Группы служб доставки
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function carrierGroups()
    {
        return $this->belongsToMany(CarrierGroup::class);
    }
}
