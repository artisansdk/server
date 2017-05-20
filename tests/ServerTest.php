<?php

namespace ArtisanSDK\Server\Tests;

use ArtisanSDK\Server\Broker;
use ArtisanSDK\Server\Contracts\Broker as BrokerInterface;
use ArtisanSDK\Server\Contracts\Manager as ManagerInterface;
use ArtisanSDK\Server\Contracts\Server as ServerInterface;
use ArtisanSDK\Server\Manager;
use ArtisanSDK\Server\Server;
use Illuminate\Queue\NullQueue;
use Illuminate\Support\Fluent;
use InvalidArgumentException;
use Mockery;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory as LoopFactory;
use React\EventLoop\LoopInterface;
use React\Socket\Server as Reactor;
use StdClass;
use Symfony\Component\Console\Output\NullOutput;

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

    /**
     * Test that server implements a fluent array for config values.
     */
    public function testConfigIsFluent()
    {
        $server = new Server();

        $this->assertSame($server->address(), $server->config('address'), 'Server should return the same config value for address as the address() method does.');
        $this->assertSame($server->port(), $server->config('port'), 'Server should return the same config value for port as the port() method does.');
        $this->assertSame(['address' => '0.0.0.0', 'port' => 8080], $server->config(), 'Server should return the default settings when calling config() without any arguments.');
        $this->assertSame($server, $server->config('address', '127.0.0.1'), 'Server should return the server after a call to config() with arguments so that fluent chaining can continue.');

        // Setting an array of config values should overset existing keys and values
        $config = ['address' => '127.0.0.1', 'port' => 8081];
        $this->assertEmpty($server->config([])->config(), 'Server should overset all config values when passing an empty array to config() method.');
        $this->assertSame($config, $server->config($config)->config(), 'Server should overset all config values when passing an array to config() method.');

        // Setting a key and value should get that key and value
        $this->assertSame('bar', $server->config('foo', 'bar')->config('foo'), 'Server should set the value when config($key, $value) is called and should get the value when config($key) is called.');

        // Config should allow dot array style keys
        $this->assertArraySubset(['foo' => ['bar' => true]], $server->config('foo.bar', true)->config(), 'Server should support a deeply nested config value to be set using dot array style keys.');
        $this->assertTrue($server->config('foo.bar'), 'Server should allow dot array style keys when getting deeply nested values.');

        // Ensure that magic calls dynamically get and set config values
        $this->assertSame('foo', $server->bar('foo')->bar(), 'Server should forward all undefined method calls to config() to get and set values dynamically.');
        $this->assertSame('secret', $server->password('secret')->password(), 'Server should support the password key.');
        $this->assertSame(100, $server->maxConnections(100)->maxConnections(), 'Server should support the max_connections key.');
        $this->assertArraySubset(['max_connections' => 100], $server->config(), 'Server should camel cased dynamic config methods to snake cased config keys.');
    }

    /**
     * Test that server supports setting services using the uses() method.
     */
    public function testServerUsesSupportedServices()
    {
        $server = new Server();

        // Manager
        $manager = new Manager();
        $this->assertSame($server, $server->uses($manager), 'Server should return the server after a call to uses() when using a Manager service.');
        $this->assertSame($manager, $server->manager(), 'Server should set the server manager when using a Manager service.');

        // Broker
        $broker = new Broker();
        $this->assertSame($server, $server->uses($broker), 'Server should return the server after a call to uses() when using a Broker service.');
        $this->assertSame($broker, $server->broker(), 'Server should set the message broker when using a Broker service.');

        // Queues
        $queue = new NullQueue();
        $this->assertSame($server, $server->uses($queue, 'foo'), 'Server should return the server after a call to uses() when using a Queue service.');
        $this->assertSame($queue, $server->connector(), 'Server should set the queue connection when using a Queue service.');
        $this->assertSame($queue, $server->manager()->connector(), 'Server should set the queue connection on the manager when using a Queue service.');
        $this->assertSame('foo', $server->queue(), 'Server should set the queue name when using a Queue service.');
        $this->assertSame('foo', $server->manager()->queue(), 'Server should set the queue name on the manager when using a Queue service.');

        // Logger
        $logger = new NullOutput();
        $this->assertSame($server, $server->uses($logger), 'Server should return the server after a call to uses() when using a Logger service.');
        $this->assertSame($logger, $server->logger(), 'Server should set the logger interface when using a Logger service.');
        $this->assertSame($logger, $server->broker()->logger(), 'Server should set the logger interface on the broker when using a Logger service.');

        // WebSocket
        $websocket = new WsServer($broker);
        $this->assertSame($server, $server->uses($websocket), 'Server should return the server after a call to uses() when using a Websocket service.');
        $this->assertSame($websocket, $server->websocket(), 'Server should set the websocket server when using a Websocket service.');

        // HTTP
        $http = new HttpServer(Mockery::mock($websocket));
        $this->assertSame($server, $server->uses($http), 'Server should return the server after a call to uses() when using an HTTP service.');
        $this->assertSame($http, $server->http(), 'Server should set the HTTP server when using an HTTP service.');

        // Socket
        $loop = LoopFactory::create();
        $socket = new IoServer($http, new Reactor($loop), $loop);
        $this->assertSame($server, $server->uses($socket), 'Server should return the server after a call to uses() when using a Socket service.');
        $this->assertSame($socket, $server->socket(), 'Server should set the socket when using a Socket service.');

        // Event Loop
        $this->assertSame($server, $server->uses($loop), 'Server should return the server after a call to uses() when using a Loop service.');
        $this->assertSame($loop, $server->loop(), 'Server should set the event loop when using a Loop service.');
        $this->assertSame($loop, $server->socket()->loop, 'Server should set the loop on the Socket service when using a Loop service.');

        // Config
        $settingsArray = ['foo' => 'bar'];
        $settingsArrayable = ['bar' => 'foo'];
        $config = new Fluent($settingsArrayable);
        $this->assertSame($server, $server->uses($settingsArray), 'Server should return the server after a call to uses() when using a Config array.');
        $this->assertSame($settingsArray, $server->config(), 'Server should set the config when using a Config array.');
        $this->assertSame($settingsArrayable, $server->uses($config)->config(), 'Server should use an Arrayable object in the same way an array would be set as a Config array.');
        $this->assertArraySubset($settingsArray, $server->uses('foo', 'bar')->config(), 'Server should use a key-value Config when passing a string as the first argument to uses() method.');

        // Unsupported services should throw exceptions
        $exceptions = 0;
        $services = [
            new StdClass(),  // test objects that exist throw exceptions because they are not supported
            StdClass::class, // test strings for classes that exist are instantiated and used but throw exceptions because they are not supported
            'foo',           // test keys without values throw exceptions
            Manager::class,  // test strings for classes that exist are instantiated and used without exception
        ];
        foreach ($services as $service) {
            try {
                $server->uses($service);
            } catch (InvalidArgumentException $e) {
                ++$exceptions;
            }
        }
        $this->assertEquals(3, $exceptions, 'Server should throw InvalidArgumentException if the class does not exist, is not supported, or is being used as a string key without a value.');
    }
}
