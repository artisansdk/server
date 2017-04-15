<?php

namespace ArtisanSDK\Server\Commands;

use ArtisanSDK\Server\Entities\Command;

class StopServer extends Command
{
    /**
     * Run the command.
     */
    public function run()
    {
        $this->dispatcher()->stop();
    }
}
