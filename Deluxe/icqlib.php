<?
/*
	icqlib.php
	--
	Just a simple ICQ library.

	25.03.2007
	Author: kanicq (http://kanicq.ru)

	Example:
	$debug = true;
	$debug_log = "";
	$icq = new ICQclient($uin, $password);
	$icq->connect();
	if ($icq->connected) {
		$icq->login();
		if ($icq->logged) {
			$status = $icq->getstatus($uin_sendto);
			$icq->send_message($uin_sendto, "You are $status, aren't you?");
		}
	}
	echo $debug_log;

*/
class ICQclient
{
	var $socket, $server, $port, $connected;
	var $uin, $password, $logged;
	var $client = array(), $sequence, $TLV = array();
	var $uin_sendto, $message;

	function ICQclient($uin, $password)
	{
		$this->server = "login.icq.com";
		$this->port = 5190;
		$this->uin = (string)$uin;
		$this->password = $password;
		$this->client = array("name" => "icqlib.php", "country" => "ru", "language" => "ru", "major" => 1, "minor" => 0, "lesser" => 0, "build" => 1);
	}

	function connect()
	{
		$this->socket = @fsockopen($this->server, $this->port);
		if (!$this->socket) return false;
		else {
			$this->connected = true;
			return true;
		}
	}

	function connect_migration()
	{
		list($server, $port) = explode(":", $this->TLV[0x05]);
		$this->socket = @fsockopen($server, $port);
	}
	
	function login()
	{
		if (!$this->connected) $this->connect();
		if (!$this->connected) return false;
		$this->receive_packet();
		$this->sequence = rand(0x0000, 0xFFFF);
		$this->send_packet("login");
		$SNAC = $this->receive_packet();
		$this->parse_SNAC($SNAC);
		if (!(@$this->TLV[0x05] and @$this->TLV[0x06])) return false;
		$this->connect_migration();
		$this->send_packet("cookie");
		$this->receive_packet();
		$this->send_packet("ready");
		$this->receive_packet();
		$this->logged = true;
		return true;
	}

	function send_message($uin, $message)
	{
		if (!$this->logged) return false;
		$this->uin_sendto = $uin;
		$this->message = $message;
		$this->send_packet("message");
		$this->receive_packet();
		return true;
	}

	function getstatus($uin)
	{
		if (!$this->logged) return false;
		$this->uin_sendto = $uin;
		$this->send_packet("userinfo");
		$SNAC = $this->receive_packet();
		list(, $subfamily) = unpack("C", $SNAC[3]);
		if ($subfamily == 6) {
			list(, $uin_length) = unpack("C", $SNAC[10]);
			$this->parse_SNAC(substr($SNAC, 15+$uin_length));
			if ($this->TLV[6]) list(, $status_code) = unpack("C", $this->TLV[6][3]);
				else $status_code = 0xFF;
			switch ($status_code) {
				case 0x00: $status = "online"; break;
				case 0x01: $status = "away"; break;
				case 0x02: $status = "dnd"; break;
				case 0x03: $status = "dnd"; break;
				case 0x04: $status = "na"; break;
				case 0x05: $status = "na"; break;
				case 0x10: $status = "occupied"; break;
				case 0x11: $status = "occupied"; break;
				case 0x13: $status = "dnd"; break;
				case 0x20: $status = "free4chat"; break;
				default: $status = "undefined"; break;
			}
		}
			else $status = "offline";
		return $status;
	}

	function disconnect()
	{
		$this->connected = $this->logged = false;
		return @fclose($this->socket);
	}

	function log_packet($packet, $type="packet")
	{
		global $debug_log;
		$debug_log .= "<p>$type:<br>";
		for ($i=0; $i<strlen($packet); $i++) {
			if (strlen(strtoupper(dechex(ord($packet[$i])))) == 1) $debug_log .= "0";
			$debug_log .= strtoupper(dechex(ord($packet[$i])))." ";
		}
		$debug_log .= "</p>";
	}

	function send_packet($type)
	{
		global $debug;
		list($channel, $SNAC) = $this->gen_SNAC($type);
		$FLAP = pack("CCnn", 0x2A, $channel, $this->sequence, strlen($SNAC));
		$packet = $FLAP.$SNAC;
		if ($debug) $this->log_packet($packet, "send");
		@fwrite($this->socket, $packet);
		$this->sequence++;
		if ($this->sequence == 0xFFFF) $this->sequence = 0x0000;
	}

	function receive_packet()
	{
		global $debug;
		$FLAP = @fread($this->socket, 6);
		list(, $length) = @unpack("n", substr($FLAP, 4, 2));
		$SNAC = @fread($this->socket, $length);
		$packet = $FLAP.$SNAC;
		if ($debug) $this->log_packet($packet, "receive");
		return $SNAC;
	}

	function gen_SNAC($type)
	{
		if ($type == "login") {
			$SNAC =
				pack("N", 1).
				$this->gen_TLV(0x01, $this->uin).
				$this->gen_TLV(0x02, xor_encrypt($this->password)).
				$this->gen_TLV(0x03, $this->client["name"]).
				$this->gen_TLV(0x16, 266, 2).
				$this->gen_TLV(0x17, $this->client["major"], 2).
				$this->gen_TLV(0x18, $this->client["minor"], 2).
				$this->gen_TLV(0x19, $this->client["lesser"], 2).
				$this->gen_TLV(0x1A, $this->client["build"], 2).
				$this->gen_TLV(0x14, 85, 4).
				$this->gen_TLV(0x0F, $this->client["language"]).
				$this->gen_TLV(0x0E, $this->client["country"]);
			$channel = 1;
		}
		if ($type == "cookie") {
			$SNAC =
				pack("N", 1).
				$this->gen_TLV(0x06, $this->TLV[0x06]);
			$channel = 1;
		}
		if ($type == "ready") {             // SNAC(01,02) - CLI_READY
			$SNAC =
				"\x00\x01\x00\x02\x00\x00\x00\x00\x00\x02\x00\x01\x00\x03\x01\x10".
				"\x02\x8A\x00\x02\x00\x01\x01\x01\x02\x8A\x00\x03\x00\x01\x01\x10". 
				"\x02\x8A\x00\x15\x00\x01\x01\x10\x02\x8A\x00\x04\x00\x01\x01\x10". 
				"\x02\x8A\x00\x06\x00\x01\x01\x10\x02\x8A\x00\x09\x00\x01\x01\x10". 
				"\x02\x8A\x00\x0A\x00\x01\x01\x10\x02\x8A";
			$channel = 2;
		}
		if ($type == "message") {           // SNAC(04,06) channel 1 - CLI_SEND_ICBM_CH1
			$this->TLV[0x0501] = pack("C", 1);
			$this->TLV[0x0101] = pack("N", 0).$this->message;
			$this->TLV[0x02] =
				$this->gen_TLV(0x0501, $this->TLV[0x0501]).
				$this->gen_TLV(0x0101, $this->TLV[0x0101]);
			$SNAC =
				pack("nnnNdnca*", 0x04, 0x06, 0, 0, microtime(), 1, strlen($this->uin_sendto), $this->uin_sendto).
				$this->gen_TLV(0x02, $this->TLV[0x02]).
				$this->gen_TLV(0x06, "");
			$channel = 2;
		}
		if ($type == "userinfo") {          // SNAC(02,05) - CLI_LOCATION_INFO_REQ
			$SNAC = pack("nnnNnca*", 0x02, 0x05, 0, 0, 1, strlen($this->uin_sendto), $this->uin_sendto);
			$channel = 2;
		}
		return array($channel, $SNAC);
	}

	function parse_SNAC($SNAC)
	{
		unset($this->TLV);
		while (strlen($SNAC) > 0) {
			list(, $type, $length) = unpack("n2", substr($SNAC, 0, 4));
			$this->TLV[$type] = substr($SNAC, 4, $length);
			$SNAC = substr($SNAC, 4+$length);
		}
	}

	function gen_TLV($type, $value, $length=false)
	{
		switch ($length) {
			case 1: $format = "C"; break;     // unsigned char (8 bit)
			case 2: $format = "n"; break;     // unsigned short (16 bit, big endian byte order)
			case 4: $format = "N"; break;     // unsigned long (32 bit, big endian byte order)
			default: $format = "a*"; break;   // NUL-padded string
		}
		if ($length === false) $length = strlen($value); 
		return pack("nn".$format, $type, $length, $value);
	}
}

function xor_encrypt($password)
{
	$roast = "\xf3\x26\x81\xc4\x39\x86\xdb\x92\x71\xa3\xb9\xe6\x53\x7a\x95\x7c";
	$xored = "";
	for ($i=0; $i<strlen($password); $i++) $xored .= chr(ord($roast[$i]) ^ ord($password[$i]));
	return $xored;
}
?>