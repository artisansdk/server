<?php

namespace ArtisanSDK\Server\Messages;

use ArtisanSDK\Server\Contracts\ClientMessage;
use ArtisanSDK\Server\Entities\Message;
use ArtisanSDK\Server\Traits\AdminProtection;

class DisconnectAll extends Message implements ClientMessage
{
    use AdminProtection;
}
