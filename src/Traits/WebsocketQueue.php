<?php

namespace ArtisanSDK\Server\Traits;

use Illuminate\Contracts\Bus\Dispatcher;

trait WebsocketQueue
{
    /**
     * Queue a job onto the queue designated as a realtime bridge.
     *
     * @param mixed $job
     *
     * @return mixed
     */
    protected function queue($job)
    {
        $job->onQueue(env('SERVER_QUEUE'))
            ->onConnection(env('SERVER_QUEUE_DRIVER', env('QUEUE_DRIVER', 'default')));

        return $this->dispatch($job);
    }

    /**
     * Dispatch a job to its appropriate handler.
     *
     * @param mixed $job
     *
     * @return mixed
     */
    protected function dispatch($job)
    {
        return app(Dispatcher::class)->dispatch($job);
    }

    /**
     * Dispatch a command to its appropriate handler in the current process.
     *
     * @param mixed $job
     *
     * @return mixed
     */
    public function dispatchNow($job)
    {
        return app(Dispatcher::class)->dispatchNow($job);
    }
}
