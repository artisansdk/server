<?php

namespace ArtisanSDK\Server\Tests;

use ArtisanSDK\Server\Contracts\Broker as BrokerInterface;
use ArtisanSDK\Server\Contracts\Manager as ManagerInterface;
use ArtisanSDK\Server\Contracts\Server as ServerInterface;
use ArtisanSDK\Server\Manager;
use ArtisanSDK\Server\Server;
use Mockery;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\LoopInterface;

class ServerTest extends TestCase
{
    /**
     * Setup test case.
     */
    public function setUp()
    {
        parent::setUp();

        $this->config->set('server.address', '0.0.0.0');
        $this->config->set('server.port', 8080);
    }

    public function tearDown()
    {
        Mockery::close();
    }

    /**
     * Test that server can be factory made as a singleton.
     */
    public function testServerMakesSingleton()
    {
        $server = Server::make();

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

    /**
     * Test that instance of server can be gotten.
     */
    public function testInstanceOfServer()
    {
        $server = new Server();
        $instance = $server->instance();

        $this->assertInstanceOf(ServerInterface::class, $instance, 'Server should implement Server interface.');
        $this->assertSame($instance, $instance->instance(), 'Server should be a singleton.');
    }

    /**
     * Test that a server can be bound to an address and port.
     */
    public function testServerCanBeBound()
    {
        $server = new Server();

        // Address and port should have default settings according to the config
        $this->assertSame('0.0.0.0', $server->address(), 'Server should have a default address value of 0.0.0.0.');
        $this->assertSame(8080, $server->port(), 'Server should have a default port value of 8080.');

        // Set address and port at the same time
        $this->assertSame($server, $server->bind('127.0.0.1', 8081), 'Server should be returned when calling bind().');
        $this->assertSame('127.0.0.1', $server->address(), 'Server should set the address when calling bind($address).');
        $this->assertSame(8081, $server->port(), 'Server should set the port when calling bind($address, $port).');

        // Set address and port as an array of bindings
        $this->assertSame($server, $server->bind(['127.0.0.2', 8082]), 'Server should be returned when calling bind().');
        $this->assertSame('127.0.0.2', $server->address(), 'Server should set the address when calling bind([$address, $port]).');
        $this->assertSame(8082, $server->port(), 'Server should set the port when calling bind([$address, $port]).');
    }

    /**
     * Test that the server can get and set the address in the config.
     */
    public function testAddressIsFluent()
    {
        $server = new Server();
        $this->assertSame('0.0.0.0', $server->address(), 'Server should have a default address value of 0.0.0.0.');
        $this->assertSame($server, $server->address('127.0.0.1'), 'Server should return the server after a call to address($address) so that fluent chaining can continue.');
        $this->assertSame('127.0.0.1', $server->address(), 'Server should return previously set address.');
        $this->assertArraySubset(['address' => '127.0.0.1'], $server->config(), 'Server should set the address in the config.');
    }

    /**
     * Test that the server can get and set the port in the config.
     */
    public function testPortIsFluent()
    {
        $server = new Server();
        $this->assertSame(8080, $server->port(), 'Server should have a default port value of 8080.');
        $this->assertSame($server, $server->port(8081), 'Server should return the server after a call to port($port) so that fluent chaining can continue.');
        $this->assertSame(8081, $server->port(), 'Server should return previously set port.');
        $this->assertArraySubset(['port' => 8081], $server->config(), 'Server should set the port in the config.');
        $this->assertSame(8082, $server->port('8082')->port(), 'Server should coerce strings to integers for port numbers.');
    }

    /**
     * Test that the server can get and set the bindings in the config.
     */
    public function testBindingsAreFluent()
    {
        $server = new Server();
        $this->assertSame(['0.0.0.0', 8080], $server->bindings(), 'Server should return the default bindings when calling bindings() without any arguments.');
        $this->assertSame($server, $server->bindings('127.0.0.1', 8081), 'Server should return the server after a call to bindings() with arguments so that fluent chaining can continue.');
        $this->assertSame(['127.0.0.1', 8081], $server->bindings('127.0.0.1', 8081)->bindings(), 'Server should return previously set bindings as an array of [$address, $port].');
        $this->assertSame('127.0.0.1', $server->address(), 'Server should set the address binding such that address() returns that value.');
        $this->assertSame(8081, $server->port(), 'Server should set the port binding such that port() returns that value.');
        $this->assertSame(['127.0.0.2', 8082], $server->bindings(['127.0.0.2', 8082])->bindings(), 'Server should allow for binding an array of bindings in the form [$address, $port].');
        $this->assertSame(['127.0.0.3', 8082], $server->bindings('127.0.0.3')->bindings(), 'Server should allow binding of just the address with only a string as the first argument to bindings($address).');
    }

    /**
     * Test that server proxies start to the manager.
     */
    public function testServerStartsManager()
    {
        $manager = Mockery::mock(Manager::class)
            ->shouldReceive('start')
            ->withNoArgs()
            ->getMock();
        $server = new Server();
        $server->manager($manager);

        $this->assertNull($server->start(), 'Server should return nothing after a call to start() so that start is the last call in the chain.');
    }

    /**
     * Test that server proxies stop to the manager.
     */
    public function testServerStopsManager()
    {
        $manager = Mockery::mock(Manager::class)
            ->shouldReceive('stop')
            ->withNoArgs()
            ->getMock();
        $server = new Server();
        $server->manager($manager);

        $this->assertNull($server->stop(), 'Server should return nothing after a call to stop() so that stop is the last call in the chain.');
    }
}
