<?php

namespace App\Http\Controllers;

use App\Http\Requests\HttpFastTestRequest;
use App\HttpFastTest;
use App\HttpTest;
use Illuminate\Routing\ControllerDispatcher;
use Illuminate\Routing\Route;

/**
 * Контроллер Http тестов
 *
 * @package App\Http\Controllers
 * @author Vladimir Tikunov <vtikunov@yandex.ru>
 */
class HttpTestController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:test');
    }

    /**
     * Список тестов
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $tests = HttpTest::query()->paginate(config('app.items_per_page'));

        return view('http-tests.index', compact('tests'));
    }

    /**
     * Страница создания различных типов тестов
     *
     * @param string $type
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create(string $type)
    {
        $testClass = HttpTest::getHttpTestClassByRoute($type);

        if (is_null($testClass)) {
            abort(404);
        }

        return view("http-tests.create.{$type}");
    }

    /**
     * Метод-диспетчер для создания различных типов тестов
     *
     * @param string $type
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function store(string $type)
    {
        return $this->dispatchStoreRequest($type);
    }

    /**
     * Создание теста Http Fast
     *
     * @param HttpFastTestRequest $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function storeHttpFastTest(HttpFastTestRequest $request)
    {
        $data = $request->input();
        $concreteTest = HttpFastTest::create($data);
        $data['test_type'] = HttpFastTest::class;
        $data['test_id'] = $concreteTest->id;
        $test = HttpTest::create($data);

        return redirect()->route('http-tests.edit', $test);
    }

    /**
     * Страница редактирования теста
     *
     * @param HttpTest $test
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(HttpTest $test)
    {
        $data = array_merge($test->toArray(), $test->type->toArray());

        $type = HttpTest::getHttpTestRouteByClass(get_class($test->type));

        return view("http-tests.edit.{$type}", compact('data'));
    }

    /**
     * Метод-диспетчер для обновления различных типов тестов
     *
     * @param HttpTest $test
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function update(HttpTest $test)
    {
        return $this->dispatchStoreRequest(HttpTest::getHttpTestRouteByClass(get_class($test->type)), 'update');
    }

    /**
     * Обновление теста Http Fast
     *
     * @param HttpFastTestRequest $request
     * @param HttpTest $test
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function updateHttpFastTest(HttpFastTestRequest $request, HttpTest $test)
    {
        $data = $request->input();
        $test->update($data);
        $test->type->update($data);

        return redirect()->route('http-tests.edit', $test);
    }

    /**
     * Метод-диспетчер для создания/обновления различных типов тестов
     *
     * @param string $type
     * @param string $operation
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    protected function dispatchStoreRequest(string $type, string $operation = 'store')
    {
        $testClass = HttpTest::getHttpTestClassByRoute($type);

        if (is_null($testClass) || !in_array($operation, ['store', 'update'])) {
            abort(404);
        }

        $action = $operation.class_basename($testClass);

        $container = app();
        $route = $container->make(Route::class);
        $controllerInstance = $container->make(self::class);

        return (new ControllerDispatcher($container))->dispatch($route, $controllerInstance, $action);
    }

}
