<?php

namespace ArtisanSDK\Server\Commands;

use ArtisanSDK\Server\Entities\Command;
use ArtisanSDK\Server\Entities\Topic;

class RegisterTopic extends Command
{
    /**
     * Save the command arguments for later when the command is run.
     *
     * @param array $arguments
     */
    public function __construct(array $arguments = [])
    {
        $this->name = array_get($arguments, 'name');
    }

    /**
     * Run the command.
     */
    public function run()
    {
        $this->dispatcher()->register(new Topic($this->name));
    }
}
