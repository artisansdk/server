<?php

namespace ArtisanSDK\Server\Commands;

use ArtisanSDK\Server\Entities\Command;

class GetJob extends Command
{
    /**
     * Run the command.
     */
    public function run()
    {
        $dispatcher = $this->dispatcher();
        $job = $dispatcher->connector()
            ->pop($dispatcher->queue());
        if ( ! $job) {
            return;
        }

        $dispatcher->work($job);
    }
}
