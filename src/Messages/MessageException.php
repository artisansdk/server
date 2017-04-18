<?php

namespace ArtisanSDK\Server\Messages;

use ArtisanSDK\Server\Contracts\ServerMessage;
use ArtisanSDK\Server\Entities\Message;
use Exception;

class MessageException extends Message implements ServerMessage
{
    /**
     * Wrap an exception as a message.
     *
     * @param \Exception $exception
     */
    public function __construct(Exception $exception)
    {
        $this->exception = get_class($exception);
        $this->message = $exception->getMessage();
        $this->code = $exception->getCode() ? $exception->getCode() : 400;
    }
}
