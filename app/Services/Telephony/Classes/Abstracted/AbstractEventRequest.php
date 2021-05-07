<?php

namespace App\Services\Telephony\Classes\Abstracted;


use App\Services\Telephony\Interfaces\EventRequestInterface;
use Illuminate\Http\Request;

/**
 * Class AbstractEventRequest
 * @package App\Services\Telephony\Classes\Abstracted
 */
abstract class AbstractEventRequest implements EventRequestInterface
{
    /**
     * original request from telephony
     *
     * @var resource|string
     */
    protected  $request;

    /**
     * AbstractEventRequest constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request->getContent();
    }

    /**
     * get request field
     *
     * @return resource|string
     */
    public function getRequest():string
    {
        return $this->request;
    }
}
