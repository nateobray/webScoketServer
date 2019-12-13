<?php
namespace obray;

class WebSocketServer
{

    /**
     * Create
     * 
     * This is a convience method that sets up a socket server with the web socket
     * handler and returns the server.
     */

    public function create(string $host, int $port, \obray\StreamContext $context=NULL)
    {
        if($context === NULL){
            $context = new \obray\StreamContext();
        }
        $socketServer = new \obray\SocketServer('tcp', $host, $port, $context);
        $socketServer->showServerStatus(false);
        $webSocketHandler = new \obray\handlers\WebSocketServer();
        $webSocketHandler->registerWebSocketHandler(new \obray\base\WebSocketBaseHandler());
        $socketServer->registerhandler($webSocketHandler);
        return $socketServer;
    }

    /**
     * Start
     * 
     * This is a convience method that sets up a socket server with the web socket
     * handler and starts the server.  It's mean to be called using the singleton
     * pattern.
     */

    public function start(string $host, int $port, \obray\StreamContext $context=NULL)
    {
        if($context === NULL){
            $context = new \obray\StreamContext();
        }
        $socketServer = new \obray\SocketServer('tcp', $host, $port, $context);
        $socketServer->showServerStatus(false);
        $webSocketHandler = new \obray\handlers\WebSocketServer();
        $webSocketHandler->registerWebSocketHandler(new \obray\base\WebSocketBaseHandler());
        $socketServer->registerhandler($webSocketHandler);
        $socketServer->start();
    }
}