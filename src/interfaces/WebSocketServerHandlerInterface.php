<?php
namespace obray\interfaces;

interface WebSocketServerHandlerInterface
{
    public function onText(string $data, \obray\SocketConnection $connection): void;
    public function onBinary(string $data, \obray\SocketConnection $connection): void;
    public function onPing(string $data, \obray\SocketConnection $connection): void;
    public function onPong(string $data, \obray\SocketConnection $connection): void;
    public function onConnect(\obray\SocketConnection $connection): void;
    public function onConnected(\obray\SocketConnection $connection): void;
    public function onConnectFailed(\obray\SocketConnection $connection): void;
    public function onWriteFailed($data, \obray\SocketConnection $connection): void;
    public function onReadFailed(\obray\SocketConnection $connection): void;
    public function onUpgrade($data, \obray\SocketConnection $connection): void;
    public function onUpgradeFailed($data, \obray\SocketConnection $connection): void;
    public function onUpgraded($data, \obray\SocketConnection $connection): void;
    public function onDisconnect(\obray\SocketConnection $connection): void;
    public function onDisconnected(\obray\SocketConnection $connection): void;
}
