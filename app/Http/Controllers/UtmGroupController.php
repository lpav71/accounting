<?php

namespace App\Http\Controllers;

use App\Http\Requests\UtmGroupRequest;
use App\UtmGroup;
use Illuminate\Http\Response;

class UtmGroupController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:analytics-report');
    }

    /**
     * Отображает список групп utm
     *
     * @return Response
     */
    public function index()
    {
        $utmGroups = UtmGroup::orderBy('sort_order')->paginate(50);

        return view('utm-groups.index', compact('utmGroups'));
    }

    /**
     * Отображение формы для создания новой группы utm
     *
     * @return Response
     */
    public function create()
    {
        return view('utm-groups.create');
    }

    /**
     * Сохранение данных из формы создания новой группы utm
     *
     * @param UtmGroupRequest $request
     * @return Response
     */
    public function store(UtmGroupRequest $request)
    {
        UtmGroup::create($request->input());

        return redirect()
            ->route('utm-groups.index')
            ->with('success', __('Utm Group created successfully'));
    }

    /**
     * Отображение формы редактирования группы utm
     *
     * @param UtmGroup $utmGroup
     * @return Response
     */
    public function edit(UtmGroup $utmGroup)
    {
        return view(
            'utm-groups.edit',
            compact('utmGroup')
        );
    }

    /**
     * Сохранение данных из формы редактирования группы utm
     *
     * @param UtmGroupRequest $request
     * @param UtmGroup $utmGroup
     * @return Response
     */
    public function update(UtmGroupRequest $request, UtmGroup $utmGroup)
    {
        $utmGroup->update($request->input());

        return redirect()
            ->route('utm-groups.index')
            ->with('success', __('Utm Group updated successfully'));
    }
}
