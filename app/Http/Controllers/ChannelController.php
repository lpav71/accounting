<?php

namespace App\Http\Controllers;

use App\Channel;
use App\Http\Requests\ChannelRequest;
use App\Http\Resources\ChannelCollection;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Mail\Message;
use Mail;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Unirest;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\ChannelCollection as ChannelCollectionResource;
use App\Manufacturer;
use App\ReductionHistory;
use App\ReferencesCompaniesTemplate;
use Illuminate\Validation\ValidationException;

/**
 * Контроллер источников задач
 *
 * @author Vladimir Tikunov <vtikunov@yandex.ru>
 */
class ChannelController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:channel-list');
        $this->middleware('permission:channel-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:channel-edit', ['only' => ['edit', 'update']]);
    }

    /**
     * Список источников
     *
     * @return Response
     */
    public function index()
    {
        $channels = Channel::orderBy('id', 'DESC')->paginate(config('app.items_per_page'));

        return view('channels.index', compact('channels'));
    }

    /**
     * Форма создания источника
     *
     * @return Response
     */
    public function create()
    {
        return view('channels.create');
    }

    /**
     * Сохранение вновь создаваемого источника
     *
     * @param ChannelRequest $request
     * @return Response
     */
    public function store(ChannelRequest $request)
    {
        Channel::create($request->input());

        $this->storeKeyFile($request);

        return redirect()->route('channels.index')->with('success', __('Channel created successfully'));
    }

    /**
     * Страница просмотра источника
     *
     * @param Channel $channel
     * @return Response
     */
    public function show(Channel $channel)
    {
        $manufacturers = Manufacturer::orderBy('name')->get();
        $reductionHistory = ReductionHistory::where('channel_id', $channel->id)->orderBy('created_at','desc')->get();

        return view('channels.show', compact('channel', 'manufacturers','reductionHistory'));
    }

    /**
     * Форма редактирования источника
     *
     * @param \App\Channel $channel
     * @return \Illuminate\Http\Response
     */
    public function edit(Channel $channel)
    {
        return view('channels.edit', compact('channel'));
    }

    /**
     * Сохранение измененного источника
     *
     * @param ChannelRequest $request
     * @param Channel $channel
     * @return Response
     */
    public function update(ChannelRequest $request, Channel $channel)
    {
        $errorMessages = [];

        $channel->update($request->input());

        $this->storeKeyFile($request);

        if ($request->check) {
            try {
                if ($channel->smtp_is_enabled) {
                    config(['mail.username' => $channel->smtp_username]);
                    config(['mail.password' => $channel->smtp_password]);
                    config(['mail.encryption' => $channel->smtp_encryption]);
                    config(['mail.host' => $channel->smtp_host]);
                    config(['mail.port' => $channel->smtp_port]);

                    $finalHtml = "Test message";

                    Mail::send(
                        'mails.default',
                        ['html' => $finalHtml],
                        function (Message $message) use ($channel) {
                            $message->from($channel->notifications_email, $channel->template_name);
                            $message->to(auth()->user()->email, auth()->user()->name)->subject('Email sent successful');
                        }
                    );
                }
            } catch (\Exception $e) {
                $errorMessages[] = __('Error in mail settings');
            }

            try {
                if ($channel->sms_is_enabled) {
                    $smsText = __('Test message');
                    $smsQueryReplacements = [
                        '{Phones}' => rawurlencode(auth()->user()->phone),
                        '{Text}' => rawurlencode($smsText),
                    ];
                    $smsQuery = $channel->sms_template;
                    foreach ($smsQueryReplacements as $search => $replace) {
                        $smsQuery = str_replace($search, $replace, $smsQuery);
                    }
                    $client = new \GuzzleHttp\Client(['verify' => false]);
                    $response = $client->get($smsQuery);
                    if ($response->getStatusCode() != 200) {
                        throw new \Exception("SMS didn't send");
                    }
                }
            } catch (\Exception $e) {
                $errorMessages[] = __('Error in sms settings');
            }

        }

        return back()->withInput($request->input())->with('success', __('Channel updated successfully'))->withErrors(
            $errorMessages
        );
    }

    /**
     * Сохранение ключевого файла Google
     *
     * @param ChannelRequest $request
     */
    protected function storeKeyFile(ChannelRequest $request)
    {
        if ($request->file('google_file')) {
            $request->file('google_file')->storeAs('keys/google', strtolower($request->name).'.json');
        }
    }

    /**
     * indexAjax получение всех каналов ajax
     *
     * @return ChannelCollection
     */
    protected function indexAjax()
    {
        return new ChannelCollectionResource(Channel::whereContentControl()->get());
    }

    /**
     * referenceCompany запрашивает у канала файл с артикульными компаниями, отправляя ему настройки для компаний
     *
     * @param Channel $channel
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|StreamedResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function referenceCompany(Channel $channel, Request $request)
    {
        $body = [];
        if (empty($request->bid_active)) {
            throw ValidationException::withMessages([__('Fill : ') . __('Bid if active')]);
        }
        if (empty($request->bid_inactive)) {
            throw ValidationException::withMessages([__('Fill : ') . __('Bid if inactive')]);
        }
        if (isset($request->manufacturers)) {
            foreach ($request->manufacturers as $manufacturer) {
                $body['manufacturers'][] = Manufacturer::find($manufacturer)->name;
            }
        }
        if (empty($channel->ya_endpoint)) {
            $errorMessages[] = __('Error in channel endpoint settings');

            return back()->withErrors($errorMessages);
        }
        $template = ReferencesCompaniesTemplate::find($request->template);
        foreach ($template->attributesToArray() as $key => $attribute) {
            $body['fields'][$key] = $attribute;
        }
        $client = new \GuzzleHttp\Client(['verify' => false]);
        $multipart = [
            [
                'name' => 'data',
                'contents' => json_encode($body, JSON_UNESCAPED_UNICODE)
            ]
        ];

        $multipart[] = [
            'name' => 'bid_active',
            'contents' => $request->bid_active
        ];
        $multipart[] = [
            'name' => 'bid_inactive',
            'contents' => $request->bid_inactive
        ];

        $file = $request->file('xlsx_file');
        if (!empty($file)) {
            if ($file->getClientOriginalExtension() != 'xlsx') {
                throw ValidationException::withMessages([__('Fill : ') . __('xlsx file')]);
            }

            $multipart[] = [
                'name'     => 'xlsx_file',
                'contents' => fopen($request->file('xlsx_file')->getRealPath(), 'r'),
                'filename' => $request->file('xlsx_file')->getClientOriginalName()
            ];
        }
        $response = $client->request('POST', $channel->ya_endpoint, [
            'multipart' => $multipart,
        ]);
        //dd($response->getBody()->getContents());
        $filename = null;
        preg_match('/filename="(.+)"/', $response->getHeader('Content-Disposition')[0], $filename);
        $filename = $filename[1];
        Storage::put('temp/' . $filename, $response->getBody()->getContents());

        return Storage::download('temp/' . $filename);
    }
}
