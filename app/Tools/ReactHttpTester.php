<?php


namespace App\Tools;


use App\HttpFastTest;
use App\HttpFastTestTick;
use App\HttpTest;
use Clue\React\Buzz\Browser;
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\LoopInterface;

/**
 * Тестер HTTP на основе React event-loop
 *
 * @package App\Tools
 * @author Vladimir Tikunov <vtikunov@yandex.ru>
 */
class ReactHttpTester
{
    /**
     * Клиент HTTP
     *
     * @var Browser
     */
    protected $client;

    /**
     * Экземпляр теста
     *
     * @var HttpTest
     */
    protected $test;

    /**
     * Экземпляр тика
     *
     * @var HttpFastTestTick|null
     */
    protected $tick;

    /**
     * Время иницализации тика
     *
     * @var float
     */
    protected $initTime;

    protected function __construct(Browser $client, HttpTest $test)
    {
        $this->client = $client;
        $this->test = $test;
    }

    /**
     * Получение инстанса тестера
     *
     * @param HttpTest $test
     * @param LoopInterface $loop
     * @return ReactHttpTester
     */
    public static function test(HttpTest $test, LoopInterface $loop): ReactHttpTester
    {
        $client = new Browser($loop);

        return new self($client, $test);
    }

    /**
     * Установка конфигурации клиента из теста
     */
    protected function setTest(): void
    {
        $this
            ->client
            ->withOptions(
                [
                    'timeout' => $this->test->type->need_response_time / 1000,
                ]
            )
            ->get($this->test->url)
            ->then(
                function (ResponseInterface $response) {
                    $this->storeResult($response);

                },
                function (\Exception $error) {
                    $this->storeError($error);
                }
            );
    }

    /**
     * Иициализация тестера
     */
    public function init(): void
    {
        $this->createTick();
        $this->setTest();
        $this->initTime = microtime(true);
    }

    /**
     * Создание тика
     */
    protected function createTick(): void
    {
        //TODO перенести ветвление в Стратегию
        switch ($this->test->test_type) {
            case HttpFastTest::class:
                $this->tick = HttpFastTestTick::create(['http_fast_test_id' => $this->test->type->id]);
                break;
            default:
                break;
        }
    }

    /**
     * Сохранение результата
     *
     * @param ResponseInterface $response
     */
    protected function storeResult(ResponseInterface $response): void
    {
        if (is_null($this->tick)) {
            return;
        }

        //TODO перенести ветвление в Стратегию
        switch (get_class($this->tick)) {
            case HttpFastTestTick::class:

                $responseTime = (int)((microtime(true) - $this->initTime) * 1000);
                $isHaveString = (bool)strstr(
                    $response->getBody()->getContents(),
                    $this->test->type->need_string_in_body
                );

                $this->tick->update(
                    [
                        'response_time' => $responseTime,
                        'is_have_need_string_in_body' => $isHaveString,
                        'is_finished' => true,
                    ]
                );

                if (!$isHaveString) {
                    $this->storeError(new \RuntimeException('No need string in body'));
                }

                break;
            default:
                break;
        }
    }

    /**
     * Сохранение ошибки
     *
     * @param \Exception $error
     */
    protected function storeError(\Exception $error): void
    {
        if (is_null($this->tick)) {
            return;
        }

        //TODO перенести ветвление в Стратегию
        switch (get_class($this->tick)) {
            case HttpFastTestTick::class:
                $this->tick->update(['is_error' => true, 'is_finished' => true, 'message' => $error->getMessage()]);
                break;
            default:
                break;
        }
    }
}
