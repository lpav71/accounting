<?php

namespace App\Http\Controllers;

use App\OrderDetailState;
use App\TaskState;
use App\TelegramReportSetting;
use App\OrderState;
use App\User;
use Illuminate\Http\Request;

class TelegramReportSettingController extends Controller
{

    /**
     * ProductAttributeController constructor.
     */
    public function __construct()
    {
        $this->middleware('permission:telegram-report-settings-list');
        $this->middleware('permission:telegram-report-settings-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:telegram-report-settings-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:telegram-report-settings-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('telegram-report-settings.index', ['settings' => TelegramReportSetting::orderBy('id', 'ASC')->paginate(15)]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $taskStates = TaskState::pluck('name','id');
        $orderStates = OrderState::pluck('name','id');
        $orderDetailStates = OrderDetailState::pluck('name','id');
        $users = User::pluck('name','id');
        return view('telegram-report-settings.create',compact('taskStates','orderStates','orderDetailStates','users'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|unique:product_attributes,name',
            'time' =>'required',
            'chat_id'=> 'required'
        ]);

        $setting = TelegramReportSetting::create($request->input());
        $setting->taskStates()->sync($request->task_states);
        $setting->orderStates()->sync($request->order_states);
        $setting->orderDetailStates()->sync($request->order_detail_states);
        $setting->users()->sync($request->users);
        return redirect()->route('telegram-report-settings.index')->with('success', 'Account added successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\TelegramReportSetting  $telegramReportSetting
     * @return \Illuminate\Http\Response
     */
    public function show(TelegramReportSetting $telegramReportSetting)
    {
        return view('telegram-report-settings.show', compact('telegramReportSetting'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\TelegramReportSetting  $telegramReportSetting
     * @return \Illuminate\Http\Response
     */
    public function edit(TelegramReportSetting $telegramReportSetting)
    {
        $currentTaskStates = $telegramReportSetting->taskStates;
        $taskStates = TaskState::pluck('name','id');
        $currentOrderStates = $telegramReportSetting->orderStates;
        $orderStates = OrderState::pluck('name','id');
        $currentOrderDetailStates = $telegramReportSetting->orderDetailStates;
        $orderDetailStates = OrderDetailState::pluck('name','id');
        $users = User::pluck('name','id');
        return view('telegram-report-settings.edit', compact('users','telegramReportSetting','currentTaskStates','taskStates','currentOrderStates','orderStates','currentOrderDetailStates','orderDetailStates'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\TelegramReportSetting  $telegramReportSetting
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, TelegramReportSetting $telegramReportSetting)
    {
        $this->validate($request, [
            'name' => 'required|unique:product_attributes,name',
            'time' =>'required',
            'chat_id'=> 'required'
        ]);

        $telegramReportSetting->update($request->input());
        $telegramReportSetting->taskStates()->sync($request->task_states);
        $telegramReportSetting->orderStates()->sync($request->order_states);
        $telegramReportSetting->orderDetailStates()->sync($request->order_detail_states);
        $telegramReportSetting->users()->sync($request->users);
        return redirect()->route('telegram-report-settings.index')->with('success', 'Account added successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\TelegramReportSetting  $telegramReportSetting
     * @return \Illuminate\Http\Response
     */
    public function destroy(TelegramReportSetting $telegramReportSetting)
    {
        $telegramReportSetting->delete();

        return redirect()->route('telegram-report-settings.index')->with('success', 'Attribute deleted successfully');
    }
}
