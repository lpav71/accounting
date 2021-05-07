<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\OverdueTask;
class OverdueTaskController extends Controller
{
    /**
     * overdueTaskController constructor.
     */
    public function __construct()
    {
        $this->middleware('permission:overdueTask-list');
        $this->middleware('permission:overdueTask-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:overdueTask-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:overdueTask-delete', ['only' => ['delete']]);
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(){
        $overdueTasks = overdueTask::orderBy('id', 'ASC')->limit(50)->get();
        return view('overdue-tasks-alerts.index', compact('overdueTasks'));
    }
    
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(){
        return view('overdue-tasks-alerts.create');
    }

     /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request){
        if(isset($request->trashold)){
            $this->validate($request, [
                'trashold' => 'required|integer|min:1',
            ]);
            $overdueTask = new overdueTask;
            $overdueTask->trashold = $request->trashold;
            $overdueTask->save();
            return redirect()->route('overdue-tasks.index');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\OrderState $orderState
     * @return \Illuminate\Http\Response
     */
    public function show(overdueTask $overdueTask)
    {
        return view('overdue-tasks-alerts.show', compact('overdueTask'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\OverdueTask $overdueTask
     * @return \Illuminate\Http\Response
     */
    public function edit(overdueTask $overdueTask ){
        return view('overdue-tasks-alerts.edit', compact('overdueTask'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \App\OverdueTask $overdueTask
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, overdueTask $overdueTask){
        if(isset($request->trashold)){
            $this->validate($request, [
                'trashold' => 'required|integer|min:1',
            ]);
            $overdueTask->update(['trashold' => $request->trashold]);
            return redirect()->route('overdue-tasks.index');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param OverdueTask $overdueTask
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy(overdueTask $overdueTask){
        $overdueTask->delete();
        return redirect()->route('overdue-tasks.index');
    }
 
}
