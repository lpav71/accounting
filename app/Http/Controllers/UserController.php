<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Http\Resources\Channel;
use App\RouteList;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\Permission\Models\Role;
use DB;

class UserController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:user-list', ['except' => ['setWorkingTime']]);
        $this->middleware('permission:user-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:user-edit', ['only' => ['edit', 'update']]);
    }

    /**
     * Отображает список пользователей
     *
     * @return Response
     */
    public function index()
    {
        $data = User::orderBy('id', 'DESC')->paginate(25);

        return view('users.index', compact('data'));
    }

    /**
     * Отображение формы для создания нового пользователя
     *
     * @return Response
     */
    public function create()
    {
        $roles = Role::all()->pluck('name', 'name');
        return view('users.create', compact('roles'));
    }

    /**
     * Сохранение данных из формы создания нового пользователя
     *
     * @param UserRequest $request
     * @return Response
     */
    public function store(UserRequest $request)
    {
        $is_courier = false;
        $errorMessages = collect();
        foreach ($request->roles as $val) {
            $role = Role::findByName($val);
            if ($role->is_courier) {
                $is_courier = true;
                break;
            }
        }
        if (!$is_courier || isset($request->phone)) {
            $user = User::create($request->input());
            $user->assignRole($request->roles);
        } else {
            $errorMessages->push(__('Укажите номер телефона'));

            return back()->withInput($request->input())->withErrors($errorMessages);
        }

        return redirect()->route('users.index')->with('success', __('User created successfully'));
    }

    /**
     * Отображение информации о пользователе
     *
     * @param User $user
     * @return Response
     */
    public function show(User $user)
    {
        return view('users.show', compact('user'));
    }

    /**
     * Отображение формы редактирования пользователя
     *
     * @param User $user
     * @return Response
     */
    public function edit(User $user)
    {
        $roles = Role::all()->pluck('name', 'name');
        $userRole = $user->roles->pluck('name', 'name');
        $routeLists = RouteList::all()->pluck('id', 'id')->prepend(__('No'), 0);
        $channels = \App\Channel::all()->pluck('name', 'id')->prepend(__('No'), 0);
        $users = User::where('id','<>',$user->id)->pluck('name','id')->prepend('','');
        return view('users.edit', compact('user', 'roles', 'userRole', 'routeLists', 'channels','users'));
    }

    /**
     * Сохранение данных из формы редактирования пользователя
     *
     * @param UserRequest $request
     * @param User $user
     * @return Response
     */
    public function update(UserRequest $request, User $user)
    {
        $is_courier = false;
        $errorMessages = collect();
        foreach ($request->roles as $val) {
            $role = Role::findByName($val);
            if ($role->is_courier && !$role->is_manager) {
                $is_courier = true;
                break;
            }
        }

        if($is_courier) {
            $routeList = RouteList::find($request->input('routeList'));
            $routeList->courier_id = $user->id;
            $routeList->update();
        }

        if (!$is_courier || isset($request->phone)) {
            $data = $request->input();
            $data['is_not_working'] = isset($data['is_not_working']) ? $data['is_not_working'] : 0;
            $data['count_operation'] = isset($data['count_operation']) ? $data['count_operation'] : 0;
            $user->update($data);

            DB::table('model_has_roles')->where('model_id', $user->id)->delete();

            $roles = $request->roles;
            if (!Role::findByName('admin')->users()->count() && !in_array('admin', $roles)) {
                $roles[] = 'admin';
            }

            $user->assignRole($roles);

            if($user->isManager() && $request->channels) {
               $user->channels()->sync($request->channels);
            }
        } else {
            $errorMessages->push(__('Укажите номер телефона'));

            return back()->withInput($request->input())->withErrors($errorMessages);
        }

        return redirect()->route('users.index')->with('success', __('User updated successfully'));
    }

    /**
     * Установка Рабочего табеля пользователя
     *
     * @param Request $request
     * @param User $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function setWorkingTIme(Request $request, User $user)
    {

        if (!$request->setTime) {

            $user->workTables()->create(
                [
                    'time_to' => Carbon::now()->addHour(),
                    'is_working' => false,
                ]
            );

            return redirect()->back();
        }

        if (!$request->time_to) {
            return redirect()->back();
        }

        $time = explode(":", $request->time_to);

        $user->workTables()->create(
            [
                'time_to' => Carbon::now()->setTime($time[0], $time[1], 00, 0),
                'is_working' => true,
            ]
        );

        return redirect()->back();
    }
}
