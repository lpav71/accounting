<?php
declare(strict_types=1);

namespace App\Services\Messenger\Classes;

use App\Channel;
use App\FastMessageTemplate;
use App\Order;
use App\Services\Messenger\Exceptions\MessengerTemplateNotFoundException;
use App\Services\Messenger\Interfaces\MessengerInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class AbstractMessenger
 * @package App\Services\Messenger\Classes
 */
abstract class AbstractMessenger implements MessengerInterface
{

    /**
     * message
     * @var
     */
    private $message;

    /**
     * destination
     * @var
     */
    private $destination;

    /**
     * sender
     * @var
     */
    private $sender;

    function fillReplacementsByOrder(Order $order): MessengerInterface
    {
        $replacements = [
            '{Order.number}' => empty($order->order_number) ? '{Order.number}' : $order->order_number,
            '{Order.date}' => empty($order->created_at->format('d.m.Y')) ? '{Order.number}' : $order->created_at->format('d.m.Y'),
            '{Order.delivery_city}' => empty($order->delivery_city) ? '{Order.delivery_city}' : $order->delivery_city,
            '{Order.delivery_address}' => empty($order->getStreetDeliveryAddress()) ? '{Order.delivery_address}' : $order->getStreetDeliveryAddress(),
            '{Order.date_estimated_delivery}' => empty($order->date_estimated_delivery) ? '{Order.date_estimated_delivery}' : Carbon::createFromFormat('d-m-Y', $order->date_estimated_delivery)->format('d.m.Y'),
            '{Order.delivery_start_time}' => empty($order->delivery_start_time) ? '{Order.delivery_start_time}' : $order->delivery_start_time,
            '{Order.delivery_end_time}' => empty($order->delivery_end_time) ? '{Order.delivery_end_time}' : $order->delivery_end_time,
            '{Order.delivery_shipping_number}' => empty($order->delivery_shipping_number) ? '{Order.delivery_shipping_number}' : $order->delivery_shipping_number,
            '{Customer.phone}' => empty($order->customer->phone) ? '{Customer.phone}' : $order->customer->phone,
            '{Customer.name}' => $order->customer->first_name . ' ' . $order->customer->last_name,
            '{Order.delivery_address_comment}' => empty($order->delivery_address_comment) ? '{Order.delivery_address_comment}' : $order->delivery_address_comment,
            '{Channel.name}' => $order->channel->name,
            '{Channel.phone}' => $order->channel->phone
        ];
        foreach ($replacements as $search => $replace) {
            $this->message = str_replace($search, $replace, $this->message);
        }

        return $this;
    }

    function getMessage(): string
    {
        return $this->message;
    }

    function setMessage(string $message): MessengerInterface
    {
        $this->message = $message;
        return $this;
    }

    function getDestination(): string
    {
        return $this->destination;
    }

    function setDestination(string $destination): MessengerInterface
    {
        $this->destination = $destination;
        return $this;
    }

    function getSender(): Channel
    {
        return $this->sender;
    }

    function setSender(Channel $sender): MessengerInterface
    {
        $this->sender = $sender;
        return $this;
    }

    public function sendOrderTrackUpdated(Order $order): bool
    {
        try {
            $template = $this->getTrackNotificationTemplate();
        } catch (MessengerTemplateNotFoundException $e) {
            return false;
        }
        $this->setSender($order->channel)->setMessage($template->message)->fillReplacementsByOrder($order);
        return $this->send();
    }

    function getTrackNotificationTemplate(): FastMessageTemplate
    {
        $template = $this->templates()->where('is_track_notification', 1)->first();
        if (empty($template)) {
            throw new MessengerTemplateNotFoundException();
        }
        return $template;

    }

    public static function templates(): Builder
    {
        return FastMessageTemplate::where('type', get_called_class()::getType());
    }

    function send(): bool
    {
        $logger = new MessageLogger($this);
        $logger->save();
        return true;
    }
}