# Server

A service-based, Laravel PHP implementation of an async, realtime, WebSocket server.

## Table of Contents

- [Installation](#installation)
    - [Configure the Environment](#configure-the-environment)
    - [Nginx Websocket Proxy Configuration](#nginx-websocket-proxy-configuration)
    - [Running the Server (Supervisord)](#running-the-server)
- [Usage Guide](#usage-guide)
    - [Extending the Server Manager](#extending-the-server-manager)
    - [Pushing Messages to the Realtime Queue](#pushing-messages-to-the-realtime-queue)
    - [Authenticating Client Messages](#authenticating-client-messages)
- [Licensing](#licensing)

## Installation

The package installs into a Laravel application like any other Laravel package:

```
composer require artisansdk/server ~1.0
```

Then in your Laravel application's `config/app.php` add the `ArtisanSDK\Server\Provider::class`
to the `providers` key. This will register the configs and Artisan commands provided
by the package. You can publish these configs to `config/server.php` by running:

```
php artisan vendor:publish --provider="ArtisanSDK\\Server\\Provider" --tag=config
```

> **Show Me:** You can see how to integrate this package by browsing the source
code of [`larandomizer/app`](http://github.com/larandomizer/app) which powers
[Larandomizer.com](http://larandomizer.com).

### Configure the Environment

You will still want to edit the `.env` file to customize environment settings.
Note that no database is used as all data is stored in memory on the server.
Restarting the server will cause all data to be lost. Below are available options
for server customization:

- `SERVER_ADDRESS` (`127.0.0.1`): sets the address the server should bind to (`0.0.0.0` would be for allowing all external connections)
- `SERVER_PORT` (`8080`): sets the port the server will listen on for websocket connections
- `SERVER_MAX_CONNECTIONS` (`100`): the server rejects new connections after this limit (set to `0` to allow unlimited)
- `SERVER_QUEUE` (`default`): the name of the queue that realtime messages will be sent to
- `SERVER_QUEUE_DRIVER` (`beanstalkd`): the driver to use for the realtime message queue
- `SERVER_KEY` (`password`): the admin password to authenticate connections against admin protected connections

### Nginx Websocket Proxy Configuration

Nginx makes the perfect lightweight frontend server for the Laravel backend
application. Additionally it can be used to proxy websockets connecting on port
`80` to the `8080` default server socket. Doing so helps get around some firewall
settings. The following should be placed just before your default `location`
directive for the Laravel application itself (e.g.: Forge's default). Using these
settings you can host websockets securely with the `wss://` protocol allowing
Nginx to handle the SSL connection and your websocket server handling basic HTTP.

```
location /server/ {
    proxy_pass http://127.0.0.1:8080;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header Host $host;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_read_timeout 5m;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
}
```

A quick note on the settings used:

- `location /server/` directs all traffic going to `/server/` to the proxy
- `proxy_pass` passes the traffic to the localhost webserver on port `8080`
- `proxy_read_timeout` customizes the connection drop to hang up idle connections
- `proxy_http_version` is the version of the websocket protocol in HTTP
- `X-Real-IP` header gives your websocket server the real IP of the client connection
- `Upgrade` and `Connection` headers instruct the browser to upgrade to websocket connection

### Running the Server

The websocket server can be ran as an console command using `php artisan server:start`
and if you pass `--help` to the command you can see additional options. You can
stop the running server by killing the process with `CMD + C` (or `CTRL + C`).

In production you would want to have Supervisor monitor the server and restart
it if ever it crashes. The demo application has a "Restart Server" command which
actually just stops the server and expects Supervisor to start it again automatically.
If you are using Laravel Forge this is pretty easy to do by adding a New Deamon
on the server with a configuration of:

- Command: `/usr/bin/php /home/forge/default/artisan server:start`
- User: `forge`

The resulting Supervisor config might be:

```
[program:server]
command=/usr/bin/php /home/forge/default/artisan server:start
autostart=true
autorestart=true
user=forge
redirect_stderr=true
startsecs=1
stdout_logfile=/home/forge/.forge/server.log
```

Forge does not add the `startsecs` by default but in practice this may be needed
to give the server ample time to start without hard exiting and forcing Supervisor
to give up on starting the process.


## Usage Guide

### Extending the Server Manager

The WebSocket server is a singleton instance that wraps brokers all connections
and messages between connected clients and the server via `Broker`. While this class
rarely needs modification, the broker collaborates with the `Manager` class. You
can think of the manager as the kernel of the application as it maintains the
initial boot state and event loop the entire time the server is running. It has
sensible defaults but will likely need extending for anything domain specific.

Simple create a new manager class in your local namespace such and include a `boot()`
method which will be called to initialize your application's custom listeners:

```php
<?php

namespace App;

use ArtisanSDK\Server\Manager as BaseManager;

class Manager extends BaseManager
{
    public function boot()
    {
        parent::boot();

        $this->listener(...);
    }
}
```

As you can see in this example it's a good idea to call the parent `boot()` method
if you want to maintain the existing behavior and simply add on new behavior. With
the class extended, you now just need to update the configuration setting in
`app/server.php` under the key `server.manager` to `App\Manager::class` so the
server knows which manager to use:

```php
<?php

return [
    'manager' => App\Manager::class,
];
```

### Pushing Messages to the Realtime Queue

By default the `ArtisanSDK\Server\Manager@boot()` method adds a queue worker to the
async event loop so that "offline" messages can be sent to the "realtime" connected
websocket clients. You can use any async driver (basically don't use `sync` as
the queue driver) but if you are using Laravel Forge it is pretty easy to use
`beanstalkd` driver. Set `SERVER_QUEUE_DRIVER` and `SERVER_QUEUE` in your `.env`
to configure the driver and queue name for your realtime messages.

To send messages from your "offline" code (e.g.: controllers, repositories, etc.)
to your "realtime" code you can `use ArtisanSDK\Server\Traits\WebsocketQueue` trait in
your caller class and then call `$this->queue(new Command)` to push server
commands into the event loop of the websocket server. Commands should run nearly
instantly though there can be some lag depending on remaining commands within the
event loop. You can tweak the timing of the worker in `ArtisanSDK\Server\Manager@boot()`
method's configuration of the worker.

### Authenticating Client Messages

There is a basic auth scheme in place which allows the server to `PromptForAuthentication`
against a connection and then remember that the connection is authenticated. This
simplifies further message processing and relies on any `ClientMessage` that must
be authenticated to implement the `authorize()` method. There are three basic
traits that can be used on any message to achieve a couple of common strategies:

- `ArtisanSDK\Server\Traits\NoProtection`: always returns true so allows any client to send the message
- `ArtisanSDK\Server\Traits\ClientProtection`: allows admin or specific connections to be authorized
- `ArtisanSDK\Server\Traits\AdminProtection`: allows only admins to be authorized to send the message


## Licensing

Copyright (c) 2017 [Artisans Collaborative](http://artisanscollaborative.com)

This package is released under the MIT license. Please see the LICENSE file
distributed with every copy of the code for commercial licensing terms.
