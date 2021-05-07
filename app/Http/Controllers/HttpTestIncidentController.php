<?php

namespace App\Http\Controllers;

use App\HttpTestIncident;

/**
 * Контроллер инцидентов тестов HTTP
 *
 * @package App\Http\Controllers
 * @author Vladimir Tikunov <vtikunov@yandex.ru>
 */
class HttpTestIncidentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:test');
    }

    /**
     * Список инцидентов
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $incidents = HttpTestIncident::query()->orderByDesc('id')->paginate(config('app.items_per_page'));

        return view('http-test-incidents.index', compact('incidents'));
    }

    /**
     * Страница просмотра инцидента
     *
     * @param HttpTestIncident $incident
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function view(HttpTestIncident $incident)
    {
        return view('http-test-incidents.view', compact('incident'));
    }
}
