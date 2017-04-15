<?php

namespace ArtisanSDK\Server\Contracts;

interface SelfHandling
{
    /**
     * Run the handler.
     *
     * @return mixed
     */
    public function handle();
}
