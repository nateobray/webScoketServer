<?php
namespace obray;

class WebSocket
{
    private $headers = NULL;
    private $SecWebSocketKey = NULL;
    private $SecWebSocketVersion = NULL;
    private $SecWebSocketExtensions = NULL;

	private $frames = [];
    private $unprocessed = false;
    
    private $maximumDecodes = 10000;
    
    public function __construct($socket, string $request)
    {
        $this->parse($request);
    }

    public function getSecWebSocketKey()
    {
        return $this->SecWebSocketKey;
    }

    public function parse($request): void
    {
        $request = explode("\n", $request);
        $this->headers = new \stdClass();
        forEach($request as $index => $line){
            if(empty($line)) continue;
            $pair = explode(": ", $line);
            if(count($pair)>1){
                $this->headers->{$pair[0]} = $pair[1];
                switch(strtolower($pair[0])){
                    case 'upgrade':
                        if(strtolower(trim($pair[1])) != 'websocket') throw new \Exception("Upgrade header invalid.");
                        break;
                    case 'connection':
                        if(strtolower(trim($pair[1])) != 'upgrade') throw new \Exception("Connection header invalid.");
                        break;
                    case 'sec-websocket-key':
                        if(strlen(base64_decode(trim($pair[1]))) !== 16 ) throw new \Exception("Sec-WebSocket-Key invalid.");
                        $this->SecWebSocketKey = trim($pair[1]);
                        break;
                    case 'sec-websocket-version':
                        if(trim($pair[1]) !== '13' ) throw new \Exception("Sec-WebSocket-Version invalid.");
                        $this->SecWebSocketVersion = (int)trim($pair[1]);
                        break;
                    case 'sec-websocket-extensions':
                        $this->SecWebSocketExtension = trim($pair[1]);
                        break;
                }
            }
        }
    }

	/**
	 * Decode
	 * 
	 * takes data from the socket and begins to construct websocket frames. When
	 * it constructs a complete set of frames (isFinal() == true) constituting a 
	 * completed message from the client, it calls the callback method.
	 */

	public function decode(string $data, $server, $socket, $callback)
	{
		$data = (($this->unprocessed!==false)?$this->unprocessed:'') . $data;
        // while we have remaining data, att
        $loops = 0;
		while($data){ 
            ++$loops;
			$frame = new \obray\WebSocketFrame();
			$data = $frame->decode($data);
			if($frame->isComplete()){
				$this->frames[] = $frame;
				if($frame->isFinal()){
					$message = '';
					forEach($this->frames as $frame){
						$payload = $frame->getPayload();
						if($payload !== false){
							$message .= $payload;
						}
					}
					$callback($this->frames[0]->getOpcode(), $message, $server, $socket);
					$this->frames = [];
				}
				$this->unprocessed = '';
			} else {
				$this->unprocessed = $data;
				$data = false;
			}
        }
        // safety feature agains a runaway process in the event of malformed data
        if($loops > $this->maximumDecodes) return;
	}
}