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

    public function onConnectFailed($socket, \obray\SocketServer $server): void
    {
        print_r("Failed!\n");
    }
    
    public function onWriteFailed($data, $socket, \obray\SocketServer $server): void
    {
        print_r("Write Failed!\n");
    }

    public function onReadFailed($socket, \obray\SocketServer $server): void
    {
        print_r("Read Failed!\n");
    }

    public function onUpgrade($data, $socket, \obray\SocketServer $server): void
    {
        print_r("upgrading to web socket connection... ");
    }
    
    public function onUpgradeFailed($data, $socket, \obray\SocketServer $server): void
    {
        $new_headers = array( 0 => "HTTP/1.1 400 Bad Request" );
        $new_headers[] = "Content-Type: text/html";
        $new_headers[] = "Content-Length: 17";
        $new_headers[] = "\r\n";
        $new_headers[] = "400 Bad Request";
        $upgradeResponse = implode("\r\n",$new_headers);
        $server->qWrite($socket, $upgradeResponse);
        print_r("Socket upgrade failed: ".$data[1]."\n");
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
