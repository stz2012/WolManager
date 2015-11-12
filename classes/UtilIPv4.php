<?php
/**
 * IPv4ユーティリティ
 * @package Util
 * @subpackage UtilIPv4
 */
class UtilIPv4
{
	protected $address;
	protected $netbits;

	/**
	 * コンストラクタ
	 */
	function __construct($address, $netbits=0)
	{
		if ($netbits == 0)
			list($address, $netbits) = explode('/', $address);
		$this->address = $address;
		$this->netbits = (int)$netbits;
	}

	// Return the IP address
	function address() { return ($this->address); }

	// Return the netbits
	function netbits() { return ($this->netbits); }

	// Return the netmask
	function netmask()
	{
		return (long2ip(ip2long('255.255.255.255')
				<< (32-$this->netbits)));
	}

	// Return the network that the address sits in
	function network()
	{
		return (long2ip((ip2long($this->address))
				& (ip2long($this->netmask()))));
	}

	// Return the broadcast that the address sits in
	function broadcast()
	{
		return (long2ip(ip2long($this->network())
				| (~(ip2long($this->netmask())))));
	}

	// Return the inverse mask of the netmask
	function inverse()
	{
		return (long2ip(~(ip2long('255.255.255.255')
				<< (32-$this->netbits))));
	}

	function getIpAddrList()
	{
		$ip_list = array();
		$start_ip = ip2long($this->network()) + 1;
		$end_ip   = ip2long($this->broadcast()) - 1;
		for ($i = $start_ip; $i <= $end_ip; $i++)
			$ip_list[] = long2ip($i);
		return $ip_list;
	}
}
?>
