<?php
/**
 * ARP情報取得関数
 * @return array - 処理結果
 */
function GetArpInfo()
{
	if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
		exec('arp -a', $output); // for Windows
	else
		exec('/usr/sbin/arp -a -n', $output); // for Linux(debian)

	$res_data = array();
	foreach ($output as $line)
	{
		if (preg_match('/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}).*([0-9a-f]{2}[\-:][0-9a-f]{2}[\-:][0-9a-f]{2}[\-:][0-9a-f]{2}[\-:][0-9a-f]{2}[\-:][0-9a-f]{2})/i', $line, $matches))
		{
			$ip_addr = $matches[1];
			$mac_addr = strtoupper(str_replace('-', ':', $matches[2]));
			if (isIgnoreAddress($ip_addr) || $mac_addr == 'FF:FF:FF:FF:FF:FF')
				continue;
			$res_data[ip2long($ip_addr)] = array(
				'ip_addr'       => $ip_addr,
				'mac_addr'      => $mac_addr
			);
		}
	}
	ksort($res_data);

	return $res_data;
}

function isIgnoreAddress($ip)
{
	//Private ranges...
	$networks = array(
		'127.0.0.0'       =>  '255.0.0.0',        //Loopback.
		'169.254.0.0'     =>  '255.255.0.0',      //Link-local.
		'224.0.0.0'       =>  '240.0.0.0',        //Multicast.
		'255.255.255.255' =>  '255.255.255.255',  //Broadcast.
		'0.0.0.0'         =>  '255.0.0.0'         //Reserved.
	);

	//inet_pton.
	$ip = @inet_pton($ip);
	if (strlen($ip) !== 4) { return false; }

	//Is the IP in a private range?
	foreach($networks as $network_address => $network_mask)
	{
		$network_address   = inet_pton($network_address);
		$network_mask      = inet_pton($network_mask);
		if (($ip & $network_mask) === $network_address)
			return true;
	}

	return false;
}

/**
 * WOL関数
 * @param $addr String - 送出先ネットワークアドレス
 * @param $mac  String - 送出先MACアドレス
 * @return bool/String - 処理結果
 */
function WakeOnLan($addr, $mac)
{
	$separator = ':';
	if ( strstr( $mac, '-' ) )
		$separator = '-';
	$addr_byte = explode($separator, $mac);

	$hw_addr = '';
	for ($i=0; $i<6; $i++)
		$hw_addr .= chr(hexdec($addr_byte[$i]));
	$msg = chr(255).chr(255).chr(255).chr(255).chr(255).chr(255);
	for ($i=1; $i<=16; $i++)
		$msg .= $hw_addr;

	$sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
	if ($sock == false)
	{
		return "Error creating socket!\nError code is '".socket_last_error($sock)."' - " . socket_strerror(socket_last_erro($sock));
	}
	else
	{
		// setting a broadcast option to socket:
		$opt_ret = socket_set_option($sock, SOL_SOCKET, SO_BROADCAST, TRUE);
		if($opt_ret < 0)
		{
			socket_close($sock);
			return "setsockopt() failed, error: " . strerror($opt_ret) . "\n";
		}
		if(socket_sendto($sock, $msg, strlen($msg), 0, $addr, 2304) === FALSE)
		{
			socket_close($sock);
			return "Magic packet failed!";
		}
		socket_close($sock);
		return TRUE;
	}
}
?>
