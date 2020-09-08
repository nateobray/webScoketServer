<?php
namespace obray\handlers;

class WebSocketServer implements \obray\interfaces\SocketServerHandlerInterface
{
	private $activeConnections = [];
	private $activeWebSockets = [];

    public function onData(string $data, \obray\interfaces\SocketConnectionInterface $connection): void
    {
		$index = array_search($connection, $this->activeConnections);
        if( $index === false ) {
			$response = $this->upgrade($data, $connection);
        } else {
			$this->activeWebSockets[$index]->decode($data, $connection, [$this, 'onMessage']);
        }
	}

	public function onMessage(int $opcode, string $msg, \obray\interfaces\SocketConnectionInterface $connection)
	{
		switch($opcode) {
			case \obray\WebSocketFrame::TEXT:
				if(!empty($this->handler)){
					$this->handler->onText($msg, $connection);
				}
				break;
			case \obray\WebSocketFrame::BINARY:
				if(!empty($this->handler)){
					$this->handler->onBinary($msg, $connection);
				}
				break;
			case \obray\WebSocketFrame::CLOSE:
				if(!empty($this->handler)){
					$this->handler->onClose($socket, $connection);
				}
				break;
			case \obray\WebSocketFrame::PING:
				if(!empty($this->handler)){
					$this->handler->onPing($socket, $connection);
				}
				break;
			case \obray\WebSocketFrame::PONG:
				if(!empty($this->handler)){
					$this->handler->onPong($socket, $connection);
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
	
	public function onDisconnect(\obray\interfaces\SocketConnectionInterface $connection): void
    {
		if(!empty($this->handler)){
			$this->handler->onDisconnect($connection);
		}
		$index = array_search($connection, $this->activeConnections);
		if($index !== false) {
			unset($this->activeConnections[$index]);
			unset($this->activeWebSockets[$index]);
		}
	}

	public function onDisconnected(\obray\interfaces\SocketConnectionInterface $connection): void 
	{
		if(!empty($this->handler)){
			$this->handler->onDisconnected($connection);
		}
	}

	public function onConnect(\obray\interfaces\SocketConnectionInterface $connection): void 
	{
		if(!empty($this->handler)){
			$this->handler->onConnect($connection);
		}
	}

	public function onConnected(\obray\interfaces\SocketConnectionInterface $connection): void 
	{
		if(!empty($this->handler)){
			$this->handler->onConnected($connection);
		}
	}

	public function onConnectFailed(\obray\interfaces\SocketConnectionInterface $connection): void
	{
		if(!empty($this->handler)){
			$this->handler->onConnectFailed($connection);
		}
	}

	public function onWriteFailed($data, \obray\interfaces\SocketConnectionInterface $connection): void
	{

	}

	public function onReadFailed(\obray\interfaces\SocketConnectionInterface $connection): void
	{
		
	}
	
	/**
	 * Upgrade
	 * 
	 * Takes an incoming HTTP request and reads off headers, parses then, and validates
	 * a valid web socket connection
	 */

    private function upgrade(string $data, \obray\interfaces\SocketConnectionInterface $connection)
    {
		if(!empty($this->handler)){
			$this->handler->onUpgrade($data, $connection);
		}
		// attempt create Web Socket
		try {
			$WebSocket = new \obray\WebSocket($data);

		// handle failed web socket upgrade request
		} catch(\Exception $e) {
			if(!empty($this->handler)){
				$this->handler->onUpgradeFailed([$data, $e->getMessage()], $connection);
				$connection->qDisconnect();
			}
			return false;
		}

		// handel successful socket upgrade request
		$this->activeConnections[] = $connection;
		$this->activeWebSockets[] = $WebSocket;
        	$new_headers = array( 0 => "HTTP/1.1 101 Switching Protocols" );
	        $new_headers[] = "Upgrade: websocket";
        	$new_headers[] = "Connection: Upgrade";
	        $secAccept = base64_encode(pack('H*', sha1($WebSocket->getSecWebSocketKey() . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
        	$new_headers[] = "Sec-WebSocket-Accept: $secAccept";
	        $new_headers[] = "\r\n";
		$upgradeResponse = implode("\r\n",$new_headers);
		
		$connection->qWrite($upgradeResponse);
		
		if(!empty($this->handler)){
			$this->handler->onUpgraded($upgradeResponse, $connection);
		}
	}

	/**
	 * Send Ping
	 * 
	 * Queues up a ping write to the socket
	 */

	private function sendPing(\obray\interfaces\SocketConnectionInterface $connection): void
	{
		$b1 = 0x89;
		$length = strlen("");
		$header = pack('CC', $b1, $length);
		$connection->qwrite($header);
	}

	/**
	 * Send Pong
	 * 
	 * This queues up a write to send a pong
	 */
	
	private function sendPong(\obray\interfaces\SocketConnectionInterface $connection): void
	{
		$b1 = 0x8A;
		$length = strlen("");
		$header = pack('CC', $b1, $length);
		$connection->qwrite($header);
	}

	/**
	 * Send Close
	 * 
	 * This queus up a write to close a connection
	 */

	private function sendClose(\obray\interfaces\SocketConnectionInterface $connection): void
	{
		$b1 = 0x88;
		$length = strlen("");
		$header = pack('CC', $b1, $length);
		$connection->qwrite($header);
		$connection->qdisconnect();
	}

	public function registerWebSocketHandler($handler): void
	{
		$this->handler = $handler;
	}
}
