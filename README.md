# Laravel WebSocket Client & Server Package

This package provides a flexible and high-performance solution that facilitates real-time communication in your Laravel
applications.

## Features

- **WebSocket Client & Server:** Real-time, low-latency, and scalable client-server communication.
- **Pub/Sub Management:** A flexible pub/sub architecture for efficient message publishing and sharing.
- **Event-Based Listening:** You can listen to events created for both the client and server to execute operations.
- **Extensible Architecture:** Driver and broker infrastructure easily extendable to meet your needs.

## Requirements

- PHP 8.2 or higher
- Laravel 10.x or higher
- [Swoole](https://swoole.com/) Extension
- Redis (For broker support)

## Installation

### Installation via Composer

Use Composer to add the package to your Laravel project:

```bash
composer require squirtle/websocket
```

## Configuration

After installing the package, publish the configuration files:

```bash
php artisan vendor:publish --provider="Squirtle\WebSocket\WebSocketServiceProvider"
```

In the published `config/websocket.php` file, you can configure Swoole, Redis, and other settings according to your
needs.

## Usage

### Starting the WebSocket Client

You can start your WebSocket client using the following Artisan command:

```bash
php artisan websocket:client
```

```bash
php artisan websocket:client --broker=redis --driver=swoole --host=127.0.0.1 --port=6001 --debug
```

### Starting the WebSocket Server

You can start your WebSocket server using the following Artisan command:

```bash
php artisan websocket:serve
```

## Event Listening

By creating listener classes, you can listen to events on the WebSocket client/server and perform actions accordingly.

## Extensibility

### Extending the WebSocket Client

To add your own WebSocket client driver instead of the default Swoole driver, implement the `ClientInterface`. Define
the
required methods and configure the package’s settings to make the new driver selectable.

### Extending the WebSocket Server

By default, the Swoole driver is used, but you can add your own WebSocket driver to meet your specific needs. To do
this, implement the `ServerInterface`, define the required methods, and make the new driver selectable from the
package’s
configuration file.

### Extending the Broker System

To integrate different broker systems besides Redis, implement the `PubSubManager` interface to create your custom
broker class. Then, configure the necessary settings in your configuration file to activate the new broker.

## Contributing

We welcome your contributions! For new feature suggestions, bug fixes, and improvements, please submit a pull request or
provide feedback in the issues section.

## License

This package is licensed under the MIT License. For details, please refer to the LICENSE file.