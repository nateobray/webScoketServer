<?php
namespace obray\base;

class WebSocketBaseHandler implements \obray\interfaces\WebSocketServerHandlerInterface
{
    public function onText(string $data, $socket, \obray\SocketServer $server): void
    {
        $server->qWrite($socket, \obray\WebSocketFrame::encode($data));
    }

    public function onBinary(string $data, $socket, \obray\SocketServer $server): void
    {
        $server->qWrite($socket, \obray\WebSocketFrame::encode($data));
    }

    public function onPing(string $data, $socket, \obray\SocketServer $server): void
    {
        $server->sendPong($socket, $data);
    }

    public function onPong(string $data, $socket, \obray\SocketServer $server): void
    {
        // do nothing
    }

    public function onConnect($socket, \obray\SocketServer $server): void
    {
        print_r("Attempting to connect to client... ");
    }

    public function onConnected($socket, \obray\SocketServer $server): void
    {
        print_r("success!\n");
    }

    public function onUpgrade($data, $socket, \obray\SocketServer $server): void
    {
        print_r("upgrading to web socket connection... ");
    }

    public function onUpgraded($data, $socket, \obray\SocketServer $server): void
    {
        print_r("Success!\n");
    }

    public function onDisconnect($socket, \obray\SocketServer $server): void
    {
        print_r("disconnecting from socket server...");
    }

    public function onDisconnected($socket, \obray\SocketServer $server): void
    {
        print_r("success!\n");
    }
}