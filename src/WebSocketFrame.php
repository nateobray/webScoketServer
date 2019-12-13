<?php
namespace obray;

class WebSocketFrame
{
    const CONTINUATION = 0x0;
    const TEXT = 0x1;
    CONST BINARY = 0x2;
    CONST RSVCTRL1 = 0x3;
    CONST RSVCTRL2 = 0x4;
    CONST RSVCTRL3 = 0x5;
    CONST RSVCTRL4 = 0x6;
    CONST RSVCTRL5 = 0x7;
    CONST CLOSE = 0x8;
    CONST PING = 0x9;
    CONST PONG = 0xA;
    CONST RSVCTRLB = 0xB;
    CONST RSVCTRLC = 0xC;
    CONST RSVCTRLD = 0xD;
    CONST RSVCTRLE = 0xE;
    CONST RSVCTRLF = 0xF;

    private $finBit;
    private $rsvBit1;
    private $rsvBit2;
    private $rsvBit3;
    private $opcode;
    private $mask;
    private $payloadLength;
    private $extendedLength;
    private $maskingKey;
    private $payload = '';
    private $isComplete = false;

    /**
     * Get Opcode
     * 
     * Returns the opcode of the frame
     */

    public function getOpcode()
    {
        return $this->opcode;
    }

    /**
     * Get Payload
     * 
     * This will return the payload data from the frame or fales if no
     * payload data is associated with this frame.
     */

    public function getPayload()
    {
        return empty($this->payload)?false:$this->payload;
    }

    /**
     * Is Final
     * 
     * This indicates that this is the last frame in a series of frames
     */

    public function isFinal()
    {
        return $this->finBit===1?true:false;
    }

    /**
     * Is Complete
     * 
     * Will return true if this is a complete frame or not partially
     * constructed from an incomplete data stream
     */

    public function isComplete()
    {
        return $this->isComplete;
    }

    /**
     * encode
     * 
     * Takes a data paramter and packages that data into a frame datastructure
     */

    public function encode($data)
    {
        $b1 = 0x80 | (0x1 & 0x0f);
		$length = mb_strlen($data, '8bit');

		if($length <= 125)
			$header = pack('CC', $b1, $length);
		elseif($length > 125 && $length < 65536)
			$header = pack('CCn', $b1, 126, $length);
		elseif($length >= 65536)
			$header = pack('CCJ', $b1, 127, $length);
		return $header . $data;
    }

    /**
     * Decode
     * 
     * Takes in data from socket and attempts to parse a frame from it
     */

    public function decode(string $data)
    {
        // a frame cannot be less than 8bytes
        if( mb_strlen($data, '8bit') < 8 ){
            $this->isComplete = false;
			return $data;
		}
        
        $decoded = unpack('H2', $data);
        $position = 1;  // track what byte we are decoding

		// get FIN bit
		$this->finBit = ($decoded[$position][0]==8)?1:0;

		// set reserved bits
		$this->rsvBit1 = 0;
		$this->rsvBit2 = 0;
        $this->rsvBit3 = 0;
        
		// get opcode (operation code)
		$this->opcode = intval($decoded[$position][1], 16);
		switch($this->opcode){
            case \obray\WebSocketFrame::CLOSE: 
            case \obray\WebSocketFrame::PING:
            case \obray\WebSocketFrame::PONG:
                return '';
            case \obray\WebSocketFrame::RSVCTRL1:
            case \obray\WebSocketFrame::RSVCTRL2:
            case \obray\WebSocketFrame::RSVCTRL3:
            case \obray\WebSocketFrame::RSVCTRL4:
            case \obray\WebSocketFrame::RSVCTRL5:
            case \obray\WebSocketFrame::RSVCTRLB:
            case \obray\WebSocketFrame::RSVCTRLC:
            case \obray\WebSocketFrame::RSVCTRLD:
            case \obray\WebSocketFrame::RSVCTRLE:
            case \obray\WebSocketFrame::RSVCTRLF:				
                print_r("Invalid opcode (".$this->opcode.")");
                exit();
                return '';
        }
        
		// get mask bit and length
		$this->mask = 0;
        $this->payloadLength = intval(unpack("H2length", $data, $position)['length'], 16);
		if($this->payloadLength > 0x80){
			$this->payloadLength = $this->payloadLength - 0x80;
			$this->mask = 1;
		}
        ++$position; // we've now decoded our second byte

		// handle medium and large length payloads (16 & 64 bit)
		if( $this->payloadLength === 126 ){
            $this->payloadLength = intval(unpack("H4length", $data, $position)['length'], 16);
            $position += 2; // length is and addition 4 bytes
		} else if( $this->payloadLength === 127 ){
            $this->payloadLength = intval(unpack("H16length", $data, $position)['length'], 16);
            $position += 6; // length is and addition 4 bytes
        }
        
        // get mask key so we can unmask our payload
        $this->maskingKey = [ord($data[$position]), ord($data[++$position]), ord($data[++$position]), ord($data[++$position])];
        ++$position;
        
        // if payload length > the length of our remaining data, kick the data back out to be combined with 
        // additional data from our socket stream
		if($this->payloadLength > (mb_strlen($data, '8bit')-$position)) {
            //print_r("Not a complete frame return\n");
            $this->isComplete = false;
			return $data;
        }

        // unmask payload data
		for($i=($position);$i<($this->payloadLength+$position);++$i){
			$char = chr(ord($data[$i]) ^ $this->maskingKey[($i-$position)%4]);
			$this->payload .= $char;
		}
        
        // return remaining data to be processed or fales
        $remaining = mb_strcut($data, $this->payloadLength+$position, NULL, '8bit');
        $this->isComplete = true;
        if(empty($remaining)){
            return false;
        } else {
            return $remaining;
        }
    }
}