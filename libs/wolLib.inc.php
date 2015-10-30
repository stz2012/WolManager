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

/**
 * 無視するIPアドレスかどうか
 * @param $ip_addr String - IPアドレス文字列
 * @return bool - 処理結果
 */
function isIgnoreAddress($ip_addr)
{
	$networks = array(
		'127.0.0.0'       =>  '255.0.0.0',        //Loopback.
		'169.254.0.0'     =>  '255.255.0.0',      //Link-local.
		'224.0.0.0'       =>  '240.0.0.0',        //Multicast.
		'255.255.255.255' =>  '255.255.255.255',  //Broadcast.
		'0.0.0.0'         =>  '255.0.0.0'         //Reserved.
	);

	$ip = @inet_pton($ip_addr);
	if (strlen($ip) !== 4) { return false; }

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
 * @return bool - 処理結果
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
		return false;
	}
	else
	{
		$opt_ret = socket_set_option($sock, SOL_SOCKET, SO_BROADCAST, true);
		if ($opt_ret < 0)
		{
			socket_close($sock);
			return false;
		}
		if (socket_sendto($sock, $msg, strlen($msg), 0, $addr, 2304) === false)
		{
			socket_close($sock);
			return false;
		}
		socket_close($sock);
		return true;
	}
}
?>
