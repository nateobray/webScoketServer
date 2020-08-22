<?php
namespace obray\base;

class WebSocketBaseHandler implements \obray\interfaces\WebSocketServerHandlerInterface
{
    public function onText(string $data, \obray\SocketConnection $connection): void
    {
        $connection->qWrite(\obray\WebSocketFrame::encode($data));
    }

    public function onBinary(string $data, \obray\SocketConnection $connection): void
    {
        $connection->qWrite(\obray\WebSocketFrame::encode($data));
    }

    public function onPing(string $data, \obray\SocketConnection $connection): void
    {
        $connection->sendPong($data);
    }

    public function onPong(string $data, \obray\SocketConnection $connection): void
    {
        // do nothing
    }

    public function onConnect(\obray\SocketConnection $connection): void
    {
        print_r("Attempting to connect to client... ");
    }

    public function onConnected(\obray\SocketConnection $connection): void
    {
        print_r("success!\n");
    }

    public function onConnectFailed(\obray\SocketConnection $connection): void
    {
        print_r("Failed!\n");
    }
    
    public function onWriteFailed($data, \obray\SocketConnection $connection): void
    {
        print_r("Write Failed!\n");
    }

    public function onReadFailed(\obray\SocketConnection $connection): void
    {
        print_r("Read Failed!\n");
    }

    public function onUpgrade($data, \obray\SocketConnection $connection): void
    {
        print_r("upgrading to web socket connection... ");
    }
    
    public function onUpgradeFailed($data, \obray\SocketConnection $connection): void
    {
        $new_headers = array( 0 => "HTTP/1.1 400 Bad Request" );
        $new_headers[] = "Content-Type: text/html";
        $new_headers[] = "Content-Length: 17";
        $new_headers[] = "\r\n";
        $new_headers[] = "400 Bad Request";
        $upgradeResponse = implode("\r\n",$new_headers);
        $connection->qWrite($upgradeResponse);
        print_r("Socket upgrade failed: ".$data[1]."\n");
    }

    public function onUpgraded($data, \obray\SocketConnection $connection): void
    {
        print_r("Success!\n");
    }

    public function onDisconnect(\obray\SocketConnection $connection): void
    {
        print_r("disconnecting from socket server...");
    }

    public function onDisconnected(\obray\SocketConnection $connection): void
    {
        print_r("success!\n");
    }
}
