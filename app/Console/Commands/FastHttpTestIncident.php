<?php

namespace App\Console\Commands;

use App\HttpFastTest;
use App\HttpFastTestTick;
use App\HttpTest;
use App\HttpTestIncident;
use Carbon\Carbon;
use Illuminate\Console\Command;
use React\EventLoop\Factory;

/**
 * Команда обработки тиков быстрых тестов HTTP и создания инцидентов
 *
 * @package App\Console\Commands
 * @author Vladimir Tikunov <vtikunov@yandex.ru>
 */
class FastHttpTestIncident extends Command
{
    /**
     * Имя и сигнатура консольной команды
     *
     * @var string
     */
    protected $signature = 'http-test:fast-incident';

    /**
     * Описание консольной команды
     *
     * @var string
     */
    protected $description = 'Демон инцидентов быстрых HTTP тестов';

    /**
     * Запуск консольной команды
     */
    public function handle()
    {
        $loop = Factory::create();

        $loop->addPeriodicTimer(
            1,
            function () {
                HttpTest::where('is_active', 1)
                    ->where('test_type', HttpFastTest::class)
                    ->get()
                    ->each(
                        function (HttpTest $httpTest) {
                            $this->checkTestTicks($httpTest);
                        }
                    );
            }
        );

        $loop->run();
    }

    /**
     * Проверка тиков конкретного теста и создание инцидентов
     *
     * @param HttpTest $httpTest
     * @throws \Exception
     */
    protected function checkTestTicks(HttpTest $httpTest): void
    {

        $lastFinishedTick = $httpTest
            ->type
            ->ticks()
            ->where('is_finished', 1)->orderByDesc('id')
            ->first();

        if (is_null($lastFinishedTick)) {
            return;
        }

        $lastOpenedIncident = HttpTestIncident::query()
            ->where('http_test_tick_type', HttpFastTestTick::class)
            ->where('http_test_id', $httpTest->id)
            ->where('is_closed', 0)
            ->orderByDesc('id')
            ->first();

        if (!$lastFinishedTick->is_error) {
            $this->incidentClose($lastOpenedIncident);

            return;
        }

        if (is_null($lastOpenedIncident)) {
            $this->incidentCreate($lastFinishedTick, $httpTest);

            return;
        }

        if ($lastOpenedIncident->tick->message !== $lastFinishedTick->message) {
            $this->incidentClose($lastOpenedIncident);
            $this->incidentCreate($lastFinishedTick, $httpTest);

            return;
        }

        $this->sendEvent($lastOpenedIncident);
    }

    /**
     * Закрытие инцидента
     *
     * @param HttpTestIncident|null $incident
     * @throws \Exception
     */
    protected function incidentClose(?HttpTestIncident $incident): void
    {
        if (!is_null($incident)) {
            $incident->update(['is_closed' => true]);
            $this->sendEvent($incident);
        }
    }

    /**
     * Создание инцидента
     *
     * @param HttpFastTestTick $tick
     * @param HttpTest $httpTest
     * @return HttpTestIncident
     * @throws \Exception
     */
    protected function incidentCreate(HttpFastTestTick $tick, HttpTest $httpTest): HttpTestIncident
    {
        $incident = HttpTestIncident::create(
            [
                'http_test_id' => $httpTest->id,
                'message_time' => Carbon::now()->subDays(1),
                'http_test_tick_type' => HttpFastTestTick::class,
                'http_test_tick_id' => $tick->id,
            ]
        );

        $this->sendEvent($incident);

        return $incident;
    }

    /**
     * Отправка оповещения
     *
     * @param HttpTestIncident $incident
     * @throws \Exception
     */
    protected function sendEvent(HttpTestIncident $incident): void
    {
        if (!$incident->test->is_message) {
            return;
        }

        if ($incident->is_closed) {
            $message = __(
                ":time  \n**Incident № :incident Closed**  \nUrl:[:url](:url).  \nCreated at: :createdAt.",
                [
                    'time' => now()->toDateTimeString(),
                    'incident' => $incident->id,
                    'url' => $incident->test->url,
                    'createdAt' => $incident->created_at->toDateTimeString(),
                ]
            );
            $this->sendTg($message);
            $incident->update(['message_time' => Carbon::now()]);

            if ($incident->created_at->diffInMinutes(new \DateTime()) >= 5) {
                $this->sendWarningTg($message);
            }

            return;
        }

        if ($incident->message_time->diffInMinutes(new \DateTime()) < 5) {
            return;
        }

        $message = __(
            ":time  \n**Warning!!! We have Incident** № :incident  \nUrl:[:url](:url).  \nMessage: :message.  \nCreated at: :createdAt.",
            [
                'time' => now()->toDateTimeString(),
                'incident' => $incident->id,
                'url' => $incident->test->url,
                'message' => $incident->tick->message,
                'createdAt' => $incident->created_at->toDateTimeString(),
            ]
        );
        $this->sendTg($message);
        $incident->update(['message_time' => Carbon::now()]);

        if ($incident->created_at->diffInMinutes(new \DateTime()) >= 5) {
            $this->sendWarningTg($message);
        }
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

    /**
     * Отправка сообщения в канал Telegram
     *
     * @param string $message
     */
    protected function sendWarningTg(string $message): void
    {
        try {

            \Telegram::bot()->sendMessage(
                [
                    'chat_id' => config('telegram.warnings_chat'),
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
