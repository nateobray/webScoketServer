<?php
namespace obray\interfaces;

interface WebSocketServerHandlerInterface
{
    public function onText(string $data, $socket, \obray\SocketServer $server): void;
    public function onBinary(string $data, $socket, \obray\SocketServer $server): void;
    public function onPing(string $data, $socket, \obray\SocketServer $server): void;
    public function onPong(string $data, $socket, \obray\SocketServer $server): void;
    public function onConnect($socket, \obray\SocketServer $server): void;
    public function onConnected($socket, \obray\SocketServer $server): void;
    public function onConnectFailed($socket, \obray\SocketServer $server): void;
    public function onWriteFailed($data, $socket, \obray\SocketServer $server): void;
    public function onReadFailed($socket, \obray\SocketServer $server): void;
    public function onUpgrade($data, $socket, \obray\SocketServer $server): void;
    public function onUpgraded($data, $socket, \obray\SocketServer $server): void;
    public function onDisconnect($socket, \obray\SocketServer $server): void;
    public function onDisconnected($socket, \obray\SocketServer $server): void;
}