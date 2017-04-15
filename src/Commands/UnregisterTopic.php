<?php

namespace ArtisanSDK\Server\Commands;

use ArtisanSDK\Server\Entities\Command;

class UnregisterTopic extends Command
{
    /**
     * Save the command arguments for later when the command is run.
     *
     * @param array $arguments
     */
    public function __construct(array $arguments = [])
    {
        $this->uuid = array_get($arguments, 'uuid');
    }

    /**
     * Run the command.
     */
    public function run()
    {
        $topic = $this->dispatcher()->topics()->uuid($this->uuid);

        $this->dispatcher()->unregister($topic);
    }
}
