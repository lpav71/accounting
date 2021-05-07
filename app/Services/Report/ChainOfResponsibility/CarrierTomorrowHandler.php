<?php


namespace App\Services\Report\ChainOfResponsibility;


use App\Services\Report\Commands\CommandInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CarrierTomorrowHandler extends AbstractHandler
{

    /**
     * @inheritDoc
     */
    public function addInfo(CommandInterface $command): CommandInterface
    {
        $command->getReportMessage()->LF()->LF();
        $command->getReportMessage()->addText(__(
            "*Report of task states :timeFrom to :timeTo*",
            [
                'timeFrom' => $command->getReportRequest()->getTimeFrom()->toDateTimeString(),
                'timeTo' => $command->getReportRequest()->getTimeTo()->toDateTimeString(),
            ]
        ));

        $date = Carbon::tomorrow();
        //$date = Carbon::create('2019', '7', '2', '0', '0', '0', null);
        $orders = DB::table('orders')->where([['delivery_city','=', 'г Москва'],['date_estimated_delivery','=',$date->toDateTimeString()]])->get();
        foreach ($orders as $order)
        {
            if(is_string($order->date_estimated_delivery)){
                $command->getReportMessage()->LF()->addText($order->date_estimated_delivery);
            }else{
                $command->getReportMessage()->LF()->addText("___");
            }
            if(is_string($order->delivery_address)){
                $command->getReportMessage()->SPC()->SPC()->addText($order->delivery_address);
            }else{
                $command->getReportMessage()->SPC()->SPC()->addText("___");
            } 
            
        }
        return $command;
    }
}