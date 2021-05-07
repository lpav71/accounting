<?php

namespace App\Http\Controllers;

use App\Carrier;
use App\CarrierGroup;
use App\Category;
use App\Channel;
use App\Configuration;
use App\ExpenseCategory;
use App\ExpenseSettings;
use App\Manufacturer;
use App\OrderDetailState;
use App\OrderState;
use App\UtmCampaign;
use Illuminate\Http\Request;

class ExpenseSettingsController extends Controller
{
    /**
     * ExpenseSettingsController constructor.
     */
    public function __construct()
    {
        $this->middleware('permission:expense-setting');
        $this->middleware('permission:expense-setting-list', ['only' => 'index']);
        $this->middleware('permission:expense-setting-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:expense-setting-edit', ['only' => ['edit', 'update', 'destroy']]);
    }

    /**
     * Отображение списка расходов
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $settings = ExpenseSettings::all();

        return view('expenses.index', compact('settings'));
    }

    /**
     * Удаление расхода
     *
     * @param ExpenseSettings $expenseSettings
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy(ExpenseSettings $expenseSetting)
    {
        $expenseSetting->carriers()->detach();
        $expenseSetting->manufacturters()->detach();
        $expenseSetting->orderStates()->detach();
        $expenseSetting->channels()->detach();
        $expenseSetting->carrierGroups()->detach();
        $expenseSetting->delete();

        return redirect()
            ->route('expense-settings.index')
            ->with('success', __('Expense deleted successfully'));
    }

    /**
     * Отображение формы создания расхода
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create()
    {
        $brands = Manufacturer::all()->pluck('name', 'id')->prepend(__('No'), 0);
        $carriers = Carrier::all()->pluck('name', 'id')->prepend(__('No'), 0);
        $utms = UtmCampaign::all()->pluck('name', 'id')->prepend(__('No'), 0);
        $categories = Category::all()->pluck('name', 'id')->prepend(__('No'), 0);
        $orderStates = OrderState::all()->where('id', '!=', OrderState::where('is_new', '=', 1)->first()->id)->pluck('name', 'id')->prepend(__('No'), 0);
        $channels = Channel::all()->pluck('name', 'id')->prepend(__('No'), 0);
        $expenseCategories = ExpenseCategory::all()->pluck('name', 'id')->prepend(__('No'), 0);

        return view('expenses.create', compact('brands', 'carriers', 'utms', 'categories', 'orderStates', 'channels', 'expenseCategories'));
    }

    /**
     * Обработка формы создания расхода
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'string|required',
            'summ' => 'string|required',
            'brand_id' => 'array',
            'carrier_id' => 'array',
            'order_state_id' => 'array',
            'channels' => 'array',
            'carrier_group_id' => 'array',
            'utm_campaign_id' => 'integer',
            'expense_category_id' => 'integer',
            'category_id' => 'integer'
        ]);

        $setting = new ExpenseSettings();
        $setting->name = $request->name;
        $setting->summ = $request->summ;
        $setting->utm_campaign_id = $request->utm_campaign_id;
        $setting->category_id = $request->category_id;
        $setting->expense_category_id = $request->expense_category_id;

        $setting->save();

        if ($request->carrier_group_id) {
            $setting->carrierGroups()->sync($request->carrier_group_id);
            $carriersIds = $setting->carrierGroups->flatMap(function (CarrierGroup $carrierGroup) {
                return $carrierGroup->carriers()->pluck('id');
            });
            $setting->carriers()->sync($carriersIds);
        } elseif ($request->carrier_id) {
            $setting->carriers()->sync($request->carrier_id);
        }

        if ($request->brand_id) {
            $setting->manufacturters()->sync($request->brand_id);
        }

        if ($request->order_state_id) {
            $setting->orderStates()->sync($request->order_state_id);
        }

        if ($request->channels) {
            $setting->channels()->sync($request->channels);
        }

        return redirect()->route('expense-settings.index')->with('success', __('Extense created successfully'));
    }

    /**
     * Обработка формы редактирования расхода
     *
     * @param Request $request
     * @param ExpenseSettings $expenseSettings
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, ExpenseSettings $expenseSetting)
    {
        $this->validate($request, [
            'name' => 'string|required',
            'summ' => 'string|required',
            'is_percent' => 'integer',
            'brand_id' => 'array',
            'carrier_id' => 'array',
            'carrier_group_id' => 'array',
            'order_state_id' => 'array',
            'channels' => 'array',
            'expense_category_id' => 'integer',
            'utm_campaign_id' => 'integer',
            'category_id' => 'integer'
        ]);

        $expenseSetting->name = $request->name;
        $expenseSetting->summ = $request->summ;
        $expenseSetting->utm_campaign_id = $request->utm_campaign_id ? $request->utm_campaign_id : null;
        $expenseSetting->category_id = $request->category_id ? $request->category_id : null;
        $expenseSetting->expense_category_id = $request->expense_category_id ? $request->expense_category_id: null;

        $expenseSetting->update();

        if ($request->carrier_group_id) {
            $expenseSetting->carrierGroups()->sync($request->carrier_group_id);
            $carriersIds = $expenseSetting->carrierGroups->flatMap(function (CarrierGroup $carrierGroup) {
                return $carrierGroup->carriers()->pluck('id');
            });
            $expenseSetting->carriers()->sync($carriersIds);
        } elseif ($request->carrier_id) {
            $expenseSetting->carriers()->sync($request->carrier_id);
        }

        $expenseSetting->carrierGroups()->sync($request->carrier_group_id);

        if ($request->carrier_group_id) {
            $carriersIds = $expenseSetting->carrierGroups->flatMap(function (CarrierGroup $carrierGroup) {
                return $carrierGroup->carriers()->pluck('id');
            });
            $expenseSetting->carriers()->sync($carriersIds);
        } else {
            $expenseSetting->carriers()->sync($request->carrier_id);
        }
        $expenseSetting->manufacturters()->sync($request->brand_id);
        $expenseSetting->orderStates()->sync($request->order_state_id);
        $expenseSetting->channels()->sync($request->channels);

        return redirect()->route('expense-settings.index')->with('success', __('Expense edit successfully'));
    }

    /**
     * Отображение формы редактирования расхода
     *
     * @param ExpenseSettings $expenseSettings
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(ExpenseSettings $expenseSetting)
    {
        $brands = Manufacturer::all()->pluck('name', 'id')->prepend(__('No'), 0);
        $carriers = Carrier::all()->pluck('name', 'id')->prepend(__('No'), 0);
        $utms = UtmCampaign::all()->pluck('name', 'id')->prepend(__('No'), 0);
        $categories = Category::all()->pluck('name', 'id')->prepend(__('No'), 0);
        $orderStates = OrderState::all()->where('id', '!=', OrderState::where('is_new', '=', 1)->first()->id)->pluck('name', 'id')->prepend(__('No'), 0);
        $channels = Channel::all()->pluck('name', 'id')->prepend(__('No'), 0);
        $expenseCategories = ExpenseCategory::all()->pluck('name', 'id')->prepend(__('No'), 0);

        return view('expenses.edit', compact('expenseSetting', 'brands', 'carriers', 'utms', 'categories', 'orderStates', 'channels', 'expenseCategories'));
    }

    /**
     * Копирование расхода
     *
     * @param ExpenseSettings $expenseSettings
     * @return \Illuminate\Http\RedirectResponse
     */
    public function copy(ExpenseSettings $expenseSettings)
    {
        /**
         * @var $newExpense ExpenseSettings
         */
        $newExpense = $expenseSettings->replicate();
        $newExpense->save();

        $expenseSettings->load(['manufacturters', 'carriers', 'orderStates', 'channels', 'carrierGroups']);

        foreach ($expenseSettings->getRelations() as $relationName => $values) {
            $newExpense->{$relationName}()->sync($values);
        }

        return redirect()->route('expense-settings.index')->with('success', __('Expense copy successfully'));
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function editStates()
    {
        $orderDetailStates = OrderDetailState::all()->pluck('name', 'id');
        $configuration = Configuration::all()->where(
            'name',
            'settings_order_detail_states_for_expenses'
        )->first();
        $values = $configuration ? json_decode($configuration->values) : [];
        $successful_states = $values->successful_states ?? [];
        $minimal_states = is_object($values) && isset($values->minimal_states) ? $values->minimal_states : [];

        return view('expenses.states', compact('orderDetailStates', 'successful_states', 'minimal_states'));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeStates(Request $request)
    {
        Configuration::updateOrCreate(
            ['name' => 'settings_order_detail_states_for_expenses'],
            [
                'values' => json_encode([
                    'successful_states' => $request->successful_states,
                    'minimal_states' => $request->minimal_states,
                ])
            ]
        );

        return redirect()->route('expense-settings.states.edit');
    }
}
