<?php

namespace App\Http\Controllers;

use App\Certificate;
use App\Channel;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class CertificateController extends Controller
{
    /**
     * CertificateController constructor.
     */
    public function __construct()
    {
        $this->middleware('permission:certificate-list', ['except' => ['getBalanceApi']]);
        $this->middleware('permission:certificate-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:certificate-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:certificate-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('certificates.index', ['certificates' => Certificate::orderBy('id', 'DESC')->paginate(30)]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('certificates.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        Certificate::create($request->input());

        return redirect()->route('certificates.index')->with('success', __('Certificate created successfully'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        return view('certificates.edit', ['certificate' => Certificate::find($id)]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        DB::transaction(function () use ($request, $id) {
            $certificate = Certificate::find($id);
            $certificate->update($request->input());
        });

        return redirect()->route('certificates.index')->with('success', __('Certificate updated successfully'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $certificate = Certificate::find($id);
        $certificate->delete();

        return redirect()->route('certificates.index')->with('success', __('Certificate deleted successfully'));
    }

    /**
     * Check balance of certificate by number
     *
     * @param Request $request
     * @return Response
     */
    public function getBalanceApi(Request $request)
    {
        //Доработать/проверить тогда когда будет на источнике чем тестировать
        $response = new Response();
        $response->header('Content-Type', 'application/json');
        if (!$request->header('token')) {
            $response->setContent(json_encode(__('Token not found')));
            $response->setStatusCode(404);
        } else {
            try {
                $channel = Channel::where(['check_certificate_token' => $request->header('token')])->first();
                /**
                 * @var Certificate $certificate
                 */
                if ($certificate = Certificate::where(['number' => $request->get('number')])->first()) {
                    if ($channel->check_certificate_token == $certificate->orderDetail->order->channel->check_certificate_token) {
                        $response->setContent(json_encode(["balance" => $certificate->getBalance()]));
                        $response->setStatusCode(200);
                    } else {
                        throw new \Exception(__('Certificate not found'));
                    }
                } else {
                    throw new \Exception(__('Certificate not found'));
                }
            } catch (\Exception $exception) {
                $response->setContent(json_encode(["error" => __('Certificate not found')]));
                $response->setStatusCode(404);
            }
        }

        return $response;
    }
}
