<?php

namespace ArtisanSDK\Server\Tests;

use ArtisanSDK\Server\Contracts\Broker as BrokerInterface;
use ArtisanSDK\Server\Contracts\Manager as ManagerInterface;
use ArtisanSDK\Server\Contracts\Server as ServerInterface;
use ArtisanSDK\Server\Server;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\LoopInterface;

class ServerTest extends TestCase
{
    /**
     * Test that server can be factory made as a singleton.
     */
    public function testServerMakesSingleton()
    {
        $this->config->set('server.port', 8080);
        $server = Server::make();

        // Instance returns a singleton
        $this->assertSame($server, Server::instance(), 'Server should be a singleton.');

        // Make never creates more than one identical instance
        try {
            Server::make();
        } catch (\React\Socket\ConnectionException $e) {
            $this->assertEquals($e->getMessage(),
                'Could not bind to tcp://0.0.0.0:8080: Address already in use',
                'Server should not make multiple instances for the same bindings.'
            );
        }

        // Server should make new instances for different bindings.
        $this->config->set('server.port', 8081);
        $this->assertNotSame($server, Server::make());

        // Looks like a server
        $this->assertInstanceOf(ServerInterface::class, $server, 'Server should implement Server interface.');
        $this->assertInstanceOf(BrokerInterface::class, $server->broker(), 'Server should have a Broker implementation.');
        $this->assertInstanceOf(ManagerInterface::class, $server->manager(), 'Server should have a Manager implementation.');
        $this->assertInstanceOf(WsServer::class, $server->websocket(), 'Server should be a WebSocket server.');
        $this->assertInstanceOf(HttpServer::class, $server->http(), 'Server should be a HTTP server.');
        $this->assertInstanceOf(IoServer::class, $server->socket(), 'Server should be a Socket server.');
        $this->assertInstanceOf(LoopInterface::class, $server->loop(), 'Server should implement an event loop.');
        $this->assertCount(2, $server->bindings(), 'Server bindings should be [address, port].');
    }
}
