<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoleRequest;
use App\Role;
use Illuminate\Http\Response;
use Spatie\Permission\Models\Permission;
use DB;

class RoleController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:role-list');
        $this->middleware('permission:role-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:role-edit', ['only' => ['edit', 'update']]);
    }

    /**
     * Отображает список ролей
     *
     * @return Response
     */
    public function index()
    {
        $roles = Role::query()->orderBy('id', 'DESC')->paginate(25);

        return view('roles.index', compact('roles'));
    }

    /**
     * Отображение формы для создания новой роли
     *
     * @return Response
     */
    public function create()
    {
        $permission = Permission::all();

        return view('roles.create', compact('permission'));
    }

    /**
     * Сохранение данных из формы создания новой роли
     *
     * @param  RoleRequest $request
     * @return Response
     */
    public function store(RoleRequest $request)
    {

        $role = Role::create($request->input());
        $role->syncPermissions($request->permission);

        return redirect()->route('roles.index')->with('success', __('Role created successfully'));
    }

    /**
     * Отображение информации о роли
     *
     * @param  Role $role
     * @return Response
     */
    public function show(Role $role)
    {
        $rolePermissions = Permission::query()
            ->join("role_has_permissions", "role_has_permissions.permission_id", "=", "permissions.id")
            ->where("role_has_permissions.role_id", $role->id)
            ->get();

        return view('roles.show', compact('role', 'rolePermissions'));
    }

    /**
     * Отображение формы редактирования роли
     *
     * @param  Role $role
     * @return Response
     */
    public function edit(Role $role)
    {
        $permission = Permission::all();
        $rolePermissions = DB::table("role_has_permissions")
            ->where("role_has_permissions.role_id", $role->id)
            ->pluck('role_has_permissions.permission_id', 'role_has_permissions.permission_id')
            ->all();

        return view('roles.edit', compact('role', 'permission', 'rolePermissions'));
    }

    /**
     * Сохранение данных из формы редактирования роли
     *
     * @param  RoleRequest $request
     * @param  Role $role
     * @return Response
     */
    public function update(RoleRequest $request, Role $role)
    {

        $data = $request->input();

        if ($role->name == 'admin') {
            $data['name'] = 'admin';
            $data['permission'] = Permission::all()->pluck('name')->toArray();
        }

        $role->update($data);

        $role->syncPermissions($data['permission']);

        return redirect()->route('roles.index')->with('success', __('Role updated successfully'));
    }
}
