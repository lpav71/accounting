<?php

namespace App\Http\Controllers;

use App\ExpenseCategory;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    /**
     * ExpenseCategoryController constructor.
     */
    public function __construct()
    {
        $this->middleware('permission:expense-setting');
        $this->middleware('permission:expense-setting-list', ['only' => 'index']);
        $this->middleware('permission:expense-setting-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:expense-setting-edit', ['only' => ['edit', 'update', 'destroy']]);
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $expenseCategories = ExpenseCategory::all();

        return view('expense-category.index', compact('expenseCategories'));
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create()
    {
        return view('expense-category.create');
    }

    public function store(Request $request)
    {
        ExpenseCategory::create($request->input());

        return redirect()->route('expense-category.index')->with('success', __('Expense category created successfully'));
    }

    /**
     * @param ExpenseCategory $expenseCategory
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(ExpenseCategory $expenseCategory)
    {
        return view('expense-category.edit', compact('expenseCategory'));
    }

    /**
     * @param Request $request
     * @param ExpenseCategory $expenseCategory
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, ExpenseCategory $expenseCategory)
    {
        $expenseCategory->update($request->input());

        return redirect()->route('expense-category.index')->with('success', __('Expense category update successfully'));
    }

    /**
     * @param ExpenseCategory $expenseCategory
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy(ExpenseCategory $expenseCategory)
    {
        $expenseCategory->delete();

        return redirect()->route('expense-category.index')->with('success', __('Expense category deleted successfully'));
    }
}
