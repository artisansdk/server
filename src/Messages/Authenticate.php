<?php

namespace ArtisanSDK\Server\Messages;

use ArtisanSDK\Server\Contracts\ClientMessage;
use ArtisanSDK\Server\Contracts\SelfHandling;
use ArtisanSDK\Server\Entities\Message;
use ArtisanSDK\Server\Server;
use ArtisanSDK\Server\Traits\NoProtection;

class Authenticate extends Message implements ClientMessage, SelfHandling
{
    use NoProtection;

    /**
     * Save the message arguments for later when the message is handled.
     *
     * @param array $arguments
     */
    public function __construct(array $arguments = [])
    {
        parent::__construct($arguments);
        $this->password = array_get($arguments, 'password');
    }

    /**
     * Handle the message.
     */
    public function handle()
    {
        if (Server::instance()->password() !== $this->password) {
            return $this->dispatcher()
                ->send(new PromptForAuthentication($this), $this->client());
        }

        $this->client()->admin(true);

        return $this->dispatcher()
            ->send(new ConnectionAuthenticated($this->client()), $this->client());
    }
}
