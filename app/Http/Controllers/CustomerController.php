<?php

namespace App\Http\Controllers;

use App\Customer;
use App\Services\SecurityService\SecurityService;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Filters\CustomerFilter;

class CustomerController extends Controller
{
    /**
     * CustomerController constructor.
     */
    public function __construct()
    {
        $this->middleware('permission:customer-list');
        $this->middleware('permission:customer-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:customer-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:customer-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Customer $customer, CustomerFilter $filters, Request $request)
    {
        $customers = $customer->filter($filters)->sortable(['id' => 'desc'])->paginate(25)->appends(
            $request->query()
        );
        return view('customers.index', compact('customers'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('customers.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'first_name' => 'required',
            'phone' => [
                'required_without:email',
                'integer',
                Rule::unique('customers', 'phone')->where(function (Builder $query) {
                    return $query->where('phone', '<>', 'null');
                }),
            ],
            'email' => [
                'required_without:phone',
                Rule::unique('customers', 'email')->where(function (Builder $query) {
                    return $query->where('email', '<>', 'null');
                }),
            ]
        ]);

        Customer::create($request->input());

        return redirect()->route('customers.index')->with('success', 'Customer created successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Customer $customer
     * @return \Illuminate\Http\Response
     */
    public function show(Customer $customer)
    {
        return view('customers.show', compact('customer'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Customer $customer
     * @return \Illuminate\Http\Response
     */
    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \App\Customer $customer
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Customer $customer)
    {
        $this->validate($request, [
            'first_name' => 'required',
            'phone' => [
                'required_without:email',
                'integer',
                Rule::unique('customers', 'phone')->ignore($customer->id)->where(function (Builder $query) {
                    return $query->where('phone', '<>', 'null');
                }),
            ],
            'email' => [
                'required_without:phone',
                Rule::unique('customers', 'email')->ignore($customer->id)->where(function (Builder $query) {
                    return $query->where('email', '<>', 'null');
                }),
            ],
        ]);

        if($request->get('phone') != $customer->phone) {
            $service = new SecurityService();
            $service->changeCustomerPhone($request->get('phone'), $customer);
            foreach ($customer->orders as $order){
                $order->comments()->create(
                    [
                        'comment' => __('Phone changed from :current to :new',[
                            'current' => $customer->phone,
                            'new' => $request->get('phone')
                        ]),
                        'user_id' => \Auth::id() ?: null,
                    ]
                );
            }
        }

        $customer->update($request->input());

        return redirect()->route('customers.index')->with('success', 'Customer updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Customer $customer
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy(Customer $customer)
    {
        if ($customer->orders()->count()) {
            return redirect()->route('customers.index')->with('warning',
                'This Customer can not be deleted, because it\'s part of orders: ' . implode(', ',
                    $customer->orders()->distinct()->pluck('id')->toArray()));
        }
        $customer->delete();

        return redirect()->route('customers.index')->with('success', 'Customer deleted successfully');
    }

    public function asyncSelector(Request $request){

        //задатки на асинхроннный поиск 
        /*$customers = Customer::select(['full_name','id'])->get()->toArray();
        $requestString = mb_strtolower($request->string);
        foreach($customers as &$customer){
            $customer['full_name'] = mb_strtolower($customer['full_name']);
            $customer['lev'] = levenshtein($customer['full_name'], $requestString);
        }
        $customers = collect($customers);
        mb_regex_encoding('UTF-8');
        mb_internal_encoding("UTF-8");
        $splittedString = preg_split('/(?<!^)(?!$)/u', $requestString);
        $customers = $customers
                ->filter(function($customer) use ($splittedString){
                    foreach($splittedString as $letter){
                        if(!stristr(mb_strtolower($customer['full_name']), (string)$letter)){
                            return false;
                        }
                    }
                    return true;
                })
                ->sortBy('lev')
                ->toArray();
        ;*/
        return response()->json(\App\Customer::select('full_name as name', 'id as value')->get(), 200, ['Content-Type' => 'application/json;charset=UTF-8', 'Charset' => 'utf-8'],JSON_UNESCAPED_UNICODE);
    }
}
