<?php


namespace App\Services\Report\ChainOfResponsibility;


use App\Call;
use App\CallEvent;
use App\Services\Report\Commands\CommandInterface;
use App\Services\Telephony\TelephonyFactory;
use App\TelephonyAccountGroup;
use Carbon\Carbon;
use Ixudra\Curl\Facades\Curl;

/**
 * Получение количества минут в исходящих и входях вызовах
 *
 * Class TelephonyHandler
 * @package App\Services\Report\ChainOfResponsibility
 */
class TelephonyHandler extends AbstractHandler
{

    /**
     * @inheritDoc
     */
    public function addInfo(CommandInterface $command): CommandInterface
    {

        $command->getReportMessage()->LF()->LF()->addText(__('Information API'));

        $calls = $this->getCalls($command->getReportRequest()->getTimeFrom(), $command->getReportRequest()->getTimeTo());
        //Входящий вызов
        $inboundCount = 0;
        $inbound = 0;
        $inboundMean = 0;
        //Исходящий вызов
        $outboundCount = 0;
        $outbound = 0;
        $outboundMean = 0;
        foreach ($calls as $call) {
            if ($call['direction'] == 'OUTBOUND') {
                $outbound += $call['duration'];
                $outboundCount++;
            } else {
                $inbound += $call['duration'];
                $inboundCount++;
            }
        }

        if($inboundCount !==0){
            $inbound /= 60000;
            $inboundMean = $inbound / $inboundCount;
        }

        if($outboundCount !== 0){
            $outbound /= 60000;
            $outboundMean = $outbound / $outboundCount;
        }

        $command->getReportMessage()->LF()->LF()->addText(__('Total min inbound'))->SPC()->addText((int) $inbound);
        $command->getReportMessage()->LF()->addText(__('Inbound mean'))->SPC()->addText(round( $inboundMean,2));
        $command->getReportMessage()->LF()->addText(__('Count record inbound'))->SPC()->addText((int) $inboundCount);

        $command->getReportMessage()->LF()->LF()->addText(__('Total min outbound'))->SPC()->addText((int) $outbound);
        $command->getReportMessage()->LF()->addText(__('Outbound mean'))->SPC()->addText(round($outboundMean,2));
        $command->getReportMessage()->LF()->addText(__('Count record outbound'))->SPC()->addText($outboundCount);


        //Получаем список вызовов
        $outgoingCount = 0;
        $ingoingCount = 0;
        $outgoingLength = 0;
        $ingoingLength = 0;
        $outMean = 0;
        $inMean = 0;
        $ingoingUnaccepted = 0;

        $calls = Call::where('created_at', '>=', $command->getReportRequest()->getTimeFrom()->format('Y-m-d H:i:s'))
            ->where('created_at', '<=', $command->getReportRequest()->getTimeTo()->format('Y-m-d H:i:s'))
            ->get();

        //заменяем Call на соответствующие телефониям use App\Services\Telephony\Interfaces\CallInterface
        $calls = $calls->map(function (Call $call) {
            $telephony = (new TelephonyFactory)->getTelephonyFactory($call->telephony_name);
            return $telephony->call()::find($call->id);
        });
        /**
         * @var $call \App\Services\Telephony\Interfaces\CallInterface
         */
        foreach ($calls as $call) {
            if ($call->isOutgoing()) {
                $outgoingCount++;
                $outgoingLength += $call->length;
            } else {
                $ingoingCount++;
                $ingoingLength += $call->length;
                if ($call->length == null) {
                    $ingoingUnaccepted++;
                }
            }
        }
        $command->getReportMessage()->LF()->LF()->addText(__('Information DB'));
        $outgoingLength /= 60;
        $ingoingLength /= 60;
        if ($outgoingLength != 0) {
            $outMean = $outgoingLength / $outgoingCount;
        }

        if ($ingoingLength != 0) {
            $inMean = $ingoingLength / $ingoingCount;
        }
        $command->getReportMessage()->LF()->addText(__(
            'Count outbound :outgoingCount , length :outgoingLength',
            [
                'outgoingCount' => $outgoingCount,
                'outgoingLength' => (int) $outgoingLength
            ]
        ));
        $command->getReportMessage()->LF()->addText(__('Calls mean'))->addText(round($outMean,2));
        $command->getReportMessage()->LF()->addText(__(
            'Count inbound :ingoingCount , length :ingoingLength',
            [
                'ingoingCount' => $ingoingCount,
                'ingoingLength' => (int) $ingoingLength
            ]
        ));
        $command->getReportMessage()->LF()->addText(__('Calls mean'))->addText(round($inMean,2));
        $command->getReportMessage()->LF()->addText(__('Count unaccepted'))->addText($ingoingUnaccepted);

        $command->getReportMessage()->LF()->LF()->addText(__('Calls by telephony groups'));

        $telephonyGroups = TelephonyAccountGroup::all();
        foreach ($telephonyGroups as $group) {
            $groupTime = 0;
            $groupCount = 0;
            foreach ($group->telephonyAccounts as $account) {
                $telephony = (new TelephonyFactory())->getTelephonyFactory($account->telephony_name);
                $sub = $telephony->subscriber()::find($account->id);
                $groupTime += $sub->getCallLengthApi($command->getReportRequest()->getTimeFrom(), $command->getReportRequest()->getTimeTo());
                $groupCount += $sub->getCallCountApi($command->getReportRequest()->getTimeFrom(), $command->getReportRequest()->getTimeTo());
            }
            $command->getReportMessage()->LF()->addText($group->name . ' ' . $groupCount . ' ' . $groupTime . 'sec');
        }
        $callEvents = CallEvent::whereBetween('created_at', [$command->getReportRequest()->getTimeFrom(), $command->getReportRequest()->getTimeTo()]);

        return $command;
    }

    /**
     * @param Carbon $dateFrom
     * @param Carbon $dateTo
     * @param int $id Начальный ID записи
     * @return array
     */
    private function getCalls(Carbon $dateFrom, Carbon $dateTo, int $id = 0): array
    {
        if ($id == 0) {
            $id = $this->getFirstCallId($dateFrom, $dateTo);
        }
        $dateFromString = $dateFrom->setTimezone('UTC')->format('Y-m-d\TH%3i%3s.u\Z');
        $dateToString = $dateTo->setTimezone('UTC')->format('Y-m-d\TH%3i%3s.u\Z');
        $token = config('telephony.beeline_token');
        $calls = Curl::to('https://cloudpbx.beeline.ru/apis/portal/records?dateFrom=' . $dateFromString .
            '&dateTo=' . $dateToString .
            '&id=' . ($id - 1))
            ->withHeader('X-MPBX-API-AUTH-TOKEN: ' . $token)
            ->asJson(true)
            ->get();
        if (count($calls) == 100) {
            return array_merge($calls, $this->getCalls($dateFrom, $dateTo, $calls[99]['id']));
        }
        return $calls;
    }

    /**
     * @param Carbon $dateFrom
     * @param Carbon $dateTo
     * @return int
     */
    private function getFirstCallId(Carbon $dateFrom, Carbon $dateTo): int
    {
        $dateFromString = $dateFrom->setTimezone('UTC')->format('Y-m-d\TH%3i%3s.u\Z');
        $dateToString = $dateTo->setTimezone('UTC')->format('Y-m-d\TH%3i%3s.u\Z');
        $token = config('telephony.beeline_token');
        $calls = Curl::to('https://cloudpbx.beeline.ru/apis/portal/records?dateFrom=' . $dateFromString .
            '&dateTo=' . $dateToString)
            ->withHeader('X-MPBX-API-AUTH-TOKEN: ' . $token)
            ->asJson(true)
            ->get();
        if (count($calls) < 0) {
            return 0;
        } else {
            return $calls[0]['id'];
        }
    }
}