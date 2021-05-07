<?php
declare(strict_types=1);

namespace App\Services\Messenger\Classes;

use App\FastMessageLog;
use App\Services\Messenger\Interfaces\MessageLoggerInterface;
use App\Services\Messenger\Interfaces\MessengerInterface;
use Illuminate\Support\Facades\Auth;

/**
 * saves messengers sent messages to database
 *
 * Class MessageLogger
 * @package App\Services\Messenger\Classes
 */
class MessageLogger implements MessageLoggerInterface
{

    /**
     * message text
     * @var string
     */
    private $message;

    /**
     * destination address
     * @var string
     */
    private $destination;

    /**
     * user who sent message
     * @var int|null
     */
    private $userId;

    /**
     * message type
     * @var string
     */
    private $type;

    /**
     * sender address
     * @var string
     */
    private $sender;


    public function __construct(MessengerInterface $messenger)
    {
        $this->message = $messenger->getMessage();

        $this->destination = $messenger->getDestination();

        $this->sender = get_class($messenger) . " " . $messenger->getSender()->messenger_settings;

        $this->userId = Auth::id();

        $this->type = $messenger->getType();

    }

    public function save(): bool
    {
        FastMessageLog::create([
            'message' => $this->message,
            'destination' => $this->destination,
            'user_id' => $this->userId,
            'type' => $this->type,
            'sender' => $this->sender
        ]);
        return true;
    }


}
