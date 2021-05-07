<?php
declare(strict_types=1);

namespace App\Services\Messenger\Interfaces;

use App\Channel;
use App\FastMessageTemplate;
use App\Order;
use Illuminate\Database\Eloquent\Builder;

/**
 *
 *
 * Interface MessengerInterface
 * @package App\Services\Messenger\Interfaces
 */
interface MessengerInterface
{

    /**
     * get templates only current messenger type
     *
     * @return Builder
     */
    static function templates(): Builder;

    /**
     * get type
     *
     * @return string
     */
    static function getType(): string;

    /**
     * fill template replacements by order
     *
     * @param Order $order
     * @return $this
     */
    function fillReplacementsByOrder(Order $order): self;

    /**
     * set message to send
     *
     * @param string $message
     * @return $this
     */
    function setMessage(string $message): self;

    /**
     * get message to send
     *
     * @return string
     */
    function getMessage(): string;

    /**
     * send message
     *
     * @return bool
     */
    function send(): bool;

    /**
     * set destination address, telephone, etc.
     *
     * @param string $destination
     * @return $this
     */
    function setDestination(string $destination): self;

    /**
     * get destination address, telephone, etc.
     *
     * @return string
     */
    function getDestination(): string;

    /**
     * set sender which sends message
     *
     * @param string $sender
     * @return $this
     */
    function setSender(Channel $sender): self;

    /**
     * get sender which sends message
     *
     * @return string
     */
    function getSender(): Channel;

    /**
     * get template template for track number notification
     *
     * @return FastMessageTemplate
     */
    function getTrackNotificationTemplate(): FastMessageTemplate;


    /**
     * send to new track number to user
     *
     * @param Order $order
     * @return bool
     */
    public function sendOrderTrackUpdated(Order $order): bool;


}