<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Foundation\Auth\User as Authenticate;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Kyslik\LaravelFilterable\Filterable;
use Kyslik\ColumnSortable\Sortable;

/**
 * App\User
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $color
 * @property string $routeList
 * @property bool $is_not_working
 * @property string $daily_token
 * @property int|null $count_operation
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Cashbox[] $cashboxes
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Operation[] $operations
 * @property-read \Illuminate\Database\Eloquent\Collection|\Spatie\Permission\Models\Permission[] $permissions
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Role[] $roles
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Store[] $stores
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User permission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User role($roles)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereUpdatedAt($value)
 * @mixin \Eloquent
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User query()
 * @property string|null $phone
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User wherePhone($value)
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\UserWorkTable[] $workTables
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Channel[] $channels
 * @property int $is_crm
 * @property int|null $alternate_user_id
 * @property-read \App\User|null $alternateUser
 * @property int|null $telegram_chat_id
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\CarrierGroup[] $carrierGroups
 */
class User extends Authenticate
{
    use Notifiable;
    use HasRoles;
    use Filterable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'color',
        'phone',
        'is_not_working',
        'daily_token',
        'count_operation',
        'is_crm',
        'alternate_user_id',
        'telegram_chat_id'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Склады пользователя
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function stores()
    {
        return $this->belongsToMany('App\Store')->withTimestamps();
    }

    /**
     * Кассы пользователя
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function cashboxes()
    {
        return $this->belongsToMany('App\Cashbox')->withTimestamps();
    }

    /**
     * Операции, где автором является пользователь
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function operations()
    {
        return $this->hasMany('App\Operation');
    }

    /**
     * Рабочие графики пользователя
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function workTables()
    {
        return $this->hasMany(UserWorkTable::class);
    }

    /**
     * Машрутные листы
     *
     * @return HasOne
     */
    public function routeList()
    {
        return $this->hasOne(RouteList::class, 'courier_id');
    }

    /**
     * Магазины курьеров
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function channels()
    {
        return $this->belongsToMany(Channel::class, 'channels_managers','manager_id');
    }

    /**
     * Форматирование цвета для сохранения в БД
     *
     * @param $value
     */
    public function setColorAttribute($value)
    {
        $this->attributes['color'] = is_null($value) ? $value : strtoupper($value);
    }

    /**
     * Имеет ли пользователь роль исполнителя задач?
     *
     * @return bool
     */
    public function isTaskPerformer(): bool
    {
        return $this
            ->roles()
            ->where('is_task_performer', 1)
            ->get()
            ->isNotEmpty();
    }

    /**
     * @return bool
     */
    public function isCourier() : bool
    {
        return $this
            ->roles()
            ->where('is_courier', 1)
            ->get()
            ->isNotEmpty();
    }

    /**
     * Имеет ли пользователь роль менеджера?
     *
     * @return bool
     */
    public function isManager(): bool
    {
        return $this
            ->roles()
            ->where('is_manager', 1)
            ->get()
            ->isNotEmpty();
    }

    /**
     * Имеет ли пользователь действующий на текущий момент Рабочий табель?
     *
     * @return bool
     */
    public function isHaveActiveWorkTable(): bool
    {
        $now = Carbon::now();

        return $this
            ->workTables()
            ->where('time_from', '<=', $now)
            ->where('time_to', '>=', $now)
            ->get()
            ->isNotEmpty();
    }

    /**
     * Активный рабочий табель
     *
     * @return UserWorkTable|null
     */
    public function getActiveWorkTable()
    {
        $now = Carbon::now();

        return $this
            ->workTables()
            ->where('time_from', '<=', $now)
            ->where('time_to', '>=', $now)
            ->first();
    }

    /**
     * Получение списка статусов заказов доступных пользователю в виде [name => id], для select
     *
     * @return array
     */
    public function getOrderStatesByRole() : array
    {
        $roles = Role::whereIn('id' , $this->roles()->pluck('id'))->get();
        $orderStates = [];
        foreach ($roles as $role) {
            $states = OrderState::whereIn('id', $role->orderStates()->pluck('order_state_id'))->pluck('name', 'id');
            foreach ($states as $key => $state) {
                $orderStates[$key] = $state;
            }
        }

        return $orderStates;
    }

    /**
     * Получение массива всех id статусов доступных пользователю
     *
     * @return array
     */
    public function getOrderStateIdsByRole() : array 
    {
        $roles = Role::whereIn('id' , $this->roles()->pluck('id'))->get();
        $roleOrderStates = [];
        foreach ($roles as $role) {
            $states = OrderState::whereIn('id', $role->orderStates()->pluck('order_state_id'))->pluck('id');
            foreach ($states as $stateId) {
                $roleOrderStates[] = $stateId;
            }
        }
        return array_unique($roleOrderStates);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object
     * @throws \Exception
     */
    public static function crm():User
    {
        $user = User::where('is_crm', 1)->first();
        if (empty($user)) {
            throw new \Exception('CRM user not set');
        }
        return $user;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function alternateUser()
    {
        return $this->belongsTo(User::class,'alternate_user_id');
    }

    /**
     * @return bool
     */
    public function isWorkingNow():bool
    {
        if(!$this->isManager()){
            return true;
        }
        return UserWorkTable::getActiveUsers()->contains($this);
    }

    /**
     * get users which is not fired
     *
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public static function getNotFired(){
        return User::where('is_not_working',0)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function carrierGroups()
    {
        return $this->belongsToMany(CarrierGroup::class);
    }

    public function ruleOrderPermission()
    {
        return $this->belongsToMany(RuleOrderPermission::class, 'rule_order_permission_users');
    }
}
