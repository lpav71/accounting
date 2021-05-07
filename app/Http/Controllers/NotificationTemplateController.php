<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Channel;
use App\NotificationTemplate;

/**
 * Class NotificationTemplateController
 * @package App\Http\Controllers
 */
class NotificationTemplateController extends Controller
{

    /**
     * NotificationTemplateController constructor.
     */
    public function __construct()
    {
        $this->middleware('permission:carrier-types-list',['only' => ['index']]);
        $this->middleware('permission:carrier-types-edit', ['only' => ['save']]);
        $this->middleware('permission:notification-template-delete', ['only' => ['ajaxDelete']]);
    }

    /**
     * @param $channelId
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index($channelId)
    {
        $channel = Channel::find($channelId);
        $notifications = NotificationTemplate::where('channel_id',$channel->id)->get();
        
        return view('channels/notification-templates', compact('channel','notifications'));   
    }

    /**
     * @param $channelId
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function save($channelId, Request $request){
        $errorMessages = collect();
        $hashedTemplates=[];
        foreach($request->templates as $template){
            $hashedTemplates[]=md5($template['state'].$template['status'].$template['carrier_type_id']);
        }
        if(count($hashedTemplates)!=count(array_unique($hashedTemplates))){
            $errorMessages->push(__('Duplicated states'));
            return back()->withErrors($errorMessages)->withInput($request->input());
        }
        foreach($request->templates as $key => $template){
            if(isset($template['id'])){
            $new = NotificationTemplate::find($template['id']);
            }else{
                $new=new NotificationTemplate();
                $new->channel_id=$channelId;
            }
            $new->order_state_id=$template['state'];
            $new->template=$template['template'];
            $new->carrier_type_id=$template['carrier_type_id'];
            $new->email_subject=$template['email_subject'];
            $template['status'] == 'is_sms' ? $new->is_sms=1 : $new->is_sms=0;
            $template['status'] == 'is_email' ? $new->is_email=1 : $new->is_email=0;
            $template['status'] == 'is_disabled' ? $new->is_disabled=1 : $new->is_disabled=0;
            $new->save();
        }
        return redirect()->back();
    }

    /**
     * @param $channelId
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ajaxDelete($channelId, Request $request){
        $toDelete = NotificationTemplate::find($request->id);
        $toDelete->delete();
        return response()->json(['response' => 'deleted']);
    }
}
