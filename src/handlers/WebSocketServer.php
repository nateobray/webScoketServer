<?php
namespace obray\handlers;

class WebSocketServer implements \obray\interfaces\SocketServerHandlerInterface
{
	private $activeSockets = [];
	private $activeWebSockets = [];

    public function onData(string $data, $socket, \obray\SocketServer $server): void
    {
		$index = array_search($socket, $this->activeSockets);
        if( $index === false ) {
			$response = $this->upgrade($data, $socket, $server);
        } else {
			$this->activeWebSockets[$index]->decode($data, $server, $socket, [$this, 'onMessage']);
        }
	}

	public function onMessage(int $opcode, string $msg, $server, $socket)
	{
		switch($opcode) {
			case \obray\WebSocketFrame::TEXT:
				if(!empty($this->handler)){
					$this->handler->onText($msg, $socket, $server);
				}
				break;
			case \obray\WebSocketFrame::BINARY:
				if(!empty($this->handler)){
					$this->handler->onBinary($msg, $socket, $server);
				}
				break;
			case \obray\WebSocketFrame::CLOSE:
				if(!empty($this->handler)){
					$this->handler->onClose($socket, $server);
				}
				break;
			case \obray\WebSocketFrame::PING:
				if(!empty($this->handler)){
					$this->handler->onPing($socket, $server);
				}
				break;
			case \obray\WebSocketFrame::PONG:
				if(!empty($this->handler)){
					$this->handler->onPong($socket, $server);
				}
				break;
		}
	}

	/**
	 * On Disconnect
	 * 
	 * Required to implement SocketServerHandlerInterface and allows us to clean
	 * up closed socket connections.
	 */
	
	public function onDisconnect($socket, \obray\SocketServer $server): void
    {
		if(!empty($this->handler)){
			$this->handler->onDisconnect($socket, $server);
		}
		$index = array_search($socket, $this->activeSockets);
		if($index !== false) {
			unset($this->activeSockets[$index]);
			unset($this->activeWebSockets[$index]);
		}
	}

	public function onDisconnected($socket, \obray\SocketServer $server): void 
	{
		if(!empty($this->handler)){
			$this->handler->onDisconnected($socket, $server);
		}
	}

	public function onConnect($socket, \obray\SocketServer $server): void 
	{
		if(!empty($this->handler)){
			$this->handler->onConnect($socket, $server);
		}
	}

	public function onConnected($socket, \obray\SocketServer $server): void 
	{
		if(!empty($this->handler)){
			$this->handler->onConnected($socket, $server);
		}
	}

	public function onConnectFailed($socket, \obray\SocketServer $server): void
	{
		if(!empty($this->handler)){
			$this->handler->onConnectFailed($socket, $server);
		}
	}

	public function onWriteFailed($data, $socket, \obray\SocketServer $server): void
	{

	}

	public function onReadFailed($socket, \obray\SocketServer $server): void
	{
		
	}
	
	/**
	 * Upgrade
	 * 
	 * Takes an incoming HTTP request and reads off headers, parses then, and validates
	 * a valid web socket connection
	 */

    private function upgrade(string $data, $socket, $server)
    {
		if(!empty($this->handler)){
			$this->handler->onUpgrade($data, $socket, $server);
		}
        $WebSocket = new \obray\WebSocket($socket, $data);
		$this->activeSockets[] = $socket;
		$this->activeWebSockets[] = $WebSocket;
        $new_headers = array( 0 => "HTTP/1.1 101 Switching Protocols" );
        $new_headers[] = "Upgrade: websocket";
        $new_headers[] = "Connection: Upgrade";
        $secAccept = base64_encode(pack('H*', sha1($WebSocket->getSecWebSocketKey() . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
        $new_headers[] = "Sec-WebSocket-Accept: $secAccept";
        $new_headers[] = "\r\n";
		$upgradeResponse = implode("\r\n",$new_headers);
		$server->qWrite($socket, $upgradeResponse);
		if(!empty($this->handler)){
			$this->handler->onUpgraded($upgradeResponse, $socket, $server);
		}
	}

	/**
	 * Send Ping
	 * 
	 * Queues up a ping write to the socket
	 */

	private function sendPing($socket, $server): void
	{
		$b1 = 0x89;
		$length = strlen("");
		$header = pack('CC', $b1, $length);
		$server->qwrite($socket, $header);
	}

	/**
	 * Send Pong
	 * 
	 * This queues up a write to send a pong
	 */
	
	private function sendPong($socket, $server): void
	{
		$b1 = 0x8A;
		$length = strlen("");
		$header = pack('CC', $b1, $length);
		$server->qwrite($socket, $header);
	}

	/**
	 * Send Close
	 * 
	 * This queus up a write to close a connection
	 */

	private function sendClose($socket, $server): void
	{
		$b1 = 0x88;
		$length = strlen("");
		$header = pack('CC', $b1, $length);
		$server->qwrite($socket, $header);
		$server->qdisconnect($socket);
	}

	public function registerWebSocketHandler($handler): void
	{
		$this->handler = $handler;
	}
}