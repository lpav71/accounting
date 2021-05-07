<?php

namespace App\Console\Commands;

use App\HttpFastTest;
use App\HttpTest;
use App\Tools\ReactHttpTester;
use Illuminate\Console\Command;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;

/**
 * Команда запуска быстрых HTTP тестов
 *
 * @package App\Console\Commands
 * @author Vladimir Tikunov <vtikunov@yandex.ru>
 */
class FastHttpTest extends Command
{
    /**
     * Имя и сигнатура консольной команды
     *
     * @var string
     */
    protected $signature = 'http-test:fast';

    /**
     * Описание консольной команды
     *
     * @var string
     */
    protected $description = 'Демон быстрых HTTP тестов';

    /**
     * Инициализированные таймеры
     *
     * @var TimerInterface[]
     */
    protected $timers = [];

    /**
     * Инициализированные тесты
     *
     * @var HttpTest[]
     */
    protected $tests = [];

    /**
     * Хэш-слепок набора тестов
     *
     * @var string
     */
    protected $testsHash = '';

    /**
     * Запуск консольной команды
     *
     * @return mixed
     */
    public function handle()
    {
        $loop = Factory::create();

        $this->refreshTimers($loop);

        $loop->addPeriodicTimer(
            30,
            function () use ($loop) {
                $this->refreshTimers($loop);
            }
        );

        $loop->run();

        return;
    }

    /**
     * Обновление таймеров (hot-reload конфигурации)
     *
     * @param LoopInterface $loop
     */
    protected function refreshTimers(LoopInterface $loop)
    {
        $httpTests = HttpTest::where('is_active', 1)
            ->where('test_type', HttpFastTest::class)
            ->get();

        $generatedHash = $this->generateTestHash($httpTests);

        if ($generatedHash === $this->testsHash) {
            return;
        }

        $this->clearTimers($loop);
        $this->testsHash = $generatedHash;

        $httpTests->each(
            function (HttpTest $httpTest) use ($loop) {
                $this->timers[$httpTest->id] = $loop->addPeriodicTimer(
                    $httpTest->period / 1000,
                    function () use ($httpTest, $loop) {
                        ReactHttpTester::test($httpTest, $loop)->init();
                    }
                );
                $this->tests[$httpTest->id] = $httpTest;
            }
        );

        $this->sendInitEvent();
    }

    /**
     * Очистка действующих таймеров
     *
     * @param LoopInterface $loop
     */
    protected function clearTimers(LoopInterface $loop): void
    {
        foreach ($this->timers as $testId => $timer) {
            $loop->cancelTimer($timer);
            unset($this->timers[$testId]);
            unset($this->tests[$testId]);
        }

        $this->sendClearEvent();
    }

    /**
     * Хэш набора тестов
     *
     * @param mixed $tests
     * @return string Хэш
     */
    protected function generateTestHash($tests): string
    {
        return md5(json_encode(serialize($tests)));
    }

    /**
     * Отправка оповещения об инициализации набора тестов
     */
    protected function sendInitEvent(): void
    {
        $message = now()."  \n";
        $message .= "Tests initialized!  \n";
        $message .= "  \n";

        foreach ($this->tests as $test) {
            $message .= "***{$test->name}***  \n";
            $message .= "Url: {$test->url}  \n";
            $message .= "Period: {$test->period} ms  \n";
            $message .= "String: {$test->type->need_string_in_body}  \n";
            $message .= "Response time: {$test->type->need_response_time} ms  \n";
            $message .= "  \n";
        }

        $this->sendTg($message);
    }

    /**
     * Отправка оповещения об очистке действующих таймеров
     */
    protected function sendClearEvent(): void
    {
        $message = now()."  \n";
        $message .= "Timers Cleared!  \n";

        $this->sendTg($message);
    }

    /**
     * Отправка сообщения в канал Telegram
     *
     * @param string $message
     */
    protected function sendTg(string $message): void
    {
        try {

            \Telegram::bot()->sendMessage(
                [
                    'chat_id' => config('telegram.technical_chat'),
                    'text' => $message,
                    'parse_mode' => 'Markdown',
                    'disable_web_page_preview' => true,
                ]
            );

        } catch (\Exception $e) {
            //TODO Надо куда-то продублировать сообщение
        }
    }
}
