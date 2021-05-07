<?php

namespace App\Http\Controllers;

use App\Channel;
use App\FastMessageTemplate;
use App\Order;
use Illuminate\Http\Request;
use App\Services\Messenger\StaticFactory as MessengerFactory;

class FastMessageTemplateController extends Controller
{



    /**
     * FastMessageTemplateController constructor.
     */
    public function __construct()
    {
        $this->middleware('permission:fast-message-template-list',['except' => 'sendByOrder']);
        $this->middleware('permission:fast-message-template-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:fast-message-template-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:fast-message-template-delete', ['only' => ['destroy']]);
        $this->middleware('permission:fast-message-send-sms|fast-message-send-email', ['only' => ['sendByOrder']]);
    }



    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $smsBalance = 'error';
        $channels = Channel::where('is_hidden', 0)->where('messenger_settings','LIKE','%sms%')->get();
        $balances = [];
        foreach($channels as $channel){
            $messenger = MessengerFactory::build('sms')->setSender($channel);
            $balances[$messenger->getLogin()] =  $messenger->getBalance();
        }
        $templates = FastMessageTemplate::orderBy('id', 'ASC')->paginate(25);
        return view('fast-message-templates.index', compact('templates', 'balances'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('fast-message-templates.create');
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
            'name' => 'string|required',
            'message' => 'string|required',
            'type' => 'string|required',
            'channels' => 'array|required'
        ]);
        $data = $request->input();
        $data['is_track_notification'] = isset($data['is_track_notification']) ? $data['is_track_notification'] : 0;

        $template = FastMessageTemplate::create($data);

        $template->channels()->sync($request->channels);

        return redirect()
            ->route('fast-message-templates.index')
            ->with('success', __('Template created successfully'));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\FastMessageTemplate  $fastMessageTemplate
     * @return \Illuminate\Http\Response
     */
    public function show(FastMessageTemplate $fastMessageTemplate)
    {
        return view('fast-message-templates.show', compact('fastMessageTemplate'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\FastMessageTemplate  $fastMessageTemplate
     * @return \Illuminate\Http\Response
     */
    public function edit(FastMessageTemplate $fastMessageTemplate)
    {
        return view('fast-message-templates.edit', compact('fastMessageTemplate'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\FastMessageTemplate  $fastMessageTemplate
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, FastMessageTemplate $fastMessageTemplate)
    {
        $this->validate($request, [
            'name' => 'string|required',
            'message' => 'string|required',
            'type' => 'string|required',
            'channels' => 'array|required'
        ]);

        $data = $request->input();
        $data['is_track_notification'] = isset($data['is_track_notification']) ? $data['is_track_notification'] : 0;

        $fastMessageTemplate->update($data);

        $fastMessageTemplate->channels()->sync($request->channels);

        return redirect()
            ->route('fast-message-templates.index')
            ->with('success', __('Template created successfully'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\FastMessageTemplate $fastMessageTemplate
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function destroy(FastMessageTemplate $fastMessageTemplate)
    {
        $fastMessageTemplate->delete();

        return redirect()->route('fast-message-templates.index')->with('success', 'Attribute deleted successfully');
    }

    /**
     * send message by order
     *
     * $_REQUEST['type'] type of message
     * $_REQUEST['order_id'] order id
     * $_REQUEST['message'] text of message
     * $_REQUEST['destination'] destination address
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     * @throws \Exception
     */
    public function sendByOrder(Request $request){
        if(empty($request->message)){
            return response(__('Empty message'), 400);
        }
        $messenger = MessengerFactory::build($request->type);
        $order = Order::find($request->order_id);
        $channel = Channel::find($order->channel_id);
        $is_sent = $messenger->setMessage($request->message)
                    ->setDestination($request->destination)
                    ->setSender($channel)
                    ->send();        
        if($is_sent){
            $order->comments()->create(
                [
                    'comment' => $request->type. ' ' .__('sent').': '.$messenger->getMessage(),
                    'user_id' => \Auth::id() ?: null,
                ]
            );
            return response('Sent', 200);
        }else{
            return response(['msg'=>__($messenger->error_message)], 500);
        }
    }
}
