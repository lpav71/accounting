<?php

namespace App\Http\Controllers;

use App\Configuration;
use App\Jobs\TelegramMessage;
use App\Services\UrgentBotService\UrgentBotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UrgentAlCallController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:user-edit', ['only' => ['editNumber', 'updateNumber']]);
    }
    
    /**
     * Метод обработки вебхука от бота
     *
     * @param Request $request
     * @return false|string
     */
    public function webhook(Request $request): string
    {
        $urgentBot = new UrgentBotService();
        if($request->message['entities'] && $request->message['entities'][0]['type'] == 'bot_command') {
            Log::channel('urgent_call_log')->info(json_encode($request->input()));
            switch ($request->message['text']) {
                case '/need':
                    $urgentBot->makeNeedCall();
                    break;
                case '/critical':
                    $urgentBot->makeCriticalCall();
                    break;
            }
            TelegramMessage::dispatch(
                [
                    'chat_id' => config('urgent-call.al_chat_id'),
                    'text' => "command: " . $request->message['text'] . ", *User: first_name - " . $request->message['from']['first_name'] . ", username - " . $request->message['from']['username'] . '*' . PHP_EOL,
                    'parse_mode' => 'Markdown',
                ],
                'urgentAl_bot'
            )->onQueue('telegram_message');
        }

        return json_encode(['ok' => true]);
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\View\View
     */
    public function editNumber()
    {
        return view('urgent-call.edit', ['configuration' => Configuration::firstOrCreate(['name' => 'Al_urgent_number'])]);
    }

    /**
     * @param Configuration $configuration
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateNumber(Configuration $configuration, Request $request)
    {
        $validator = Validator::make(
            $request->input(),
            [
                'number' => 'required|regex:/^[\+]{0,1}[0-9\-\(\)\s]+$/|min:11',
            ]
        );
        if ($validator->fails()) {
            return back()->withInput($request->input())->withErrors($validator->getMessageBag()->getMessages());
        }

        $configuration->update(['values' => $request->get('number')]);

        return redirect()->route('urgent-call.edit');
    }
}
