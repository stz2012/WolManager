<?php
/**
 * SQLiteユーティリティ
 * @package Util
 * @subpackage UtilSQLite
 */
class UtilSQLite
{
	/**
	 * @var object 接続インスタンス
	 */
	protected static $connInst = null;

	/**
	 * @var object PDOインスタンス
	 */
	protected $db = false;

	/**
	 * コンストラクタ
	 */
	function __construct()
	{
		if (self::isConnect())
		{
			$this->db = self::$connInst;
			UtilLog::writeLog("PDOインスタンスの再利用: ".print_r(self::$connInst, true), 'DEBUG');
			return;
		}

		$initDb = false;
		if (file_exists(DB_FILEPATH))
		{
			if (filesize(DB_FILEPATH) == 0)
				$initDb = true;
		}
		else
			$initDb = true;

		try
		{
			$this->db = new PDO('sqlite:'.DB_FILEPATH);
			$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

			if ($initDb)
			{
				// ＤＢ初期スクリプト
				$sql = <<<SQL_TEXT
DROP TABLE IF EXISTS `wol_setting`;
CREATE TABLE `wol_setting` (
  `sid`        INTEGER PRIMARY KEY AUTOINCREMENT,
  `item_name`  VARCHAR NOT NULL,
  `item_value` TEXT NOT NULL
);
DROP TABLE IF EXISTS `wol_user`;
CREATE TABLE `wol_user` (
  `sid`       INTEGER PRIMARY KEY AUTOINCREMENT,
  `user_name` VARCHAR NOT NULL,
  `user_pass` VARCHAR NOT NULL
);
DROP TABLE IF EXISTS `wol_device`;
CREATE TABLE `wol_device` (
  `sid`         INTEGER PRIMARY KEY AUTOINCREMENT,
  `mac_addr`    VARCHAR NOT NULL,
  `device_name` VARCHAR NOT NULL
);
DROP TABLE IF EXISTS `wol_vendor`;
CREATE TABLE `wol_vendor` (
  `sid`         INTEGER PRIMARY KEY AUTOINCREMENT,
  `mac_header`  VARCHAR NOT NULL,
  `vendor_name` VARCHAR NOT NULL
);
SQL_TEXT;
				$this->db->exec($sql);

				// 暗号化キー生成
				$sql = "INSERT INTO wol_setting (";
				$sql .= "item_name, item_value";
				$sql .= ") VALUES (";
				$sql .= "?, ?";
				$sql .= ")";
				$stmt = $this->db->prepare($sql);
				$stmt->bindValue(1, 'CRYPT_KEY');
				$stmt->bindValue(2, UtilString::getRandomString(32));
				$stmt->execute();
				$stmt->closeCursor();

				// ベンダー情報更新
				self::updateVendorInfo();
			}
		}
		catch (PDOException $e)
		{
			UtilLog::writeLog($e->getMessage());
		}
	}

	/**
	 * 接続状態を判定
	 * @return bool
	 */
	public static function isConnect()
	{
		return (self::$connInst != null);
	}

	/**
	 * 暗号化キーを取得
	 * @return string
	 */
	public static function getCryptKey()
	{
		$retval = '';

		try
		{
			$db_obj = new self();
			$sql = "SELECT item_value FROM wol_setting";
			$sql .= " WHERE item_name = ?";
			$stmt = $db_obj->db->prepare($sql);
			$stmt->bindValue(1, 'CRYPT_KEY');
			$stmt->execute();
			$result = $stmt->fetchColumn();
			if ($result !== false)
				$retval = $result;
			$stmt->closeCursor();
		}
		catch (PDOException $e)
		{
			UtilLog::writeLog($e->getMessage());
		}

		return $retval;
	}

	/**
	 * ユーザ情報を取得
	 * @param string $user_name ユーザ名
	 * @return string
	 */
	public static function getUserInfo($user_name)
	{
		$retval = '';

		try
		{
			$db_obj = new self();
			$sql = "SELECT sid FROM wol_user";
			$sql .= " WHERE user_name = ?";
			$stmt = $db_obj->db->prepare($sql);
			$stmt->bindValue(1, $user_name);
			$stmt->execute();
			$result = $stmt->fetchColumn();
			if ($result !== false)
				$retval = $result;
			$stmt->closeCursor();
		}
		catch (PDOException $e)
		{
			UtilLog::writeLog($e->getMessage());
		}

		return $retval;
	}

	/**
	 * ユーザ一覧を取得
	 * @return array
	 */
	public static function getUserList()
	{
		$retval = array();

		try
		{
			$db_obj = new self();
			$sql = "SELECT * FROM wol_user";
			$sql .= " ORDER BY user_name";
			$stmt = $db_obj->db->prepare($sql);
			$stmt->execute();
			while ($row = $stmt->fetch(PDO::FETCH_ASSOC))
			{
				$retval[$row['sid']] = array(
					'user_name' => $row['user_name'],
					'user_pass' => $row['user_pass']
				);
			}
			$stmt->closeCursor();
		}
		catch (PDOException $e)
		{
			UtilLog::writeLog($e->getMessage());
		}

		return $retval;
	}

	/**
	 * ユーザ情報を追加
	 * @param string $user_name ユーザ名
	 * @param string $user_pass ユーザパスワード
	 * @return bool
	 */
	public static function addUserInfo($user_name, $user_pass)
	{
		$retval = false;

		try
		{
			$db_obj = new self();
			$sql = "INSERT INTO wol_user (";
			$sql .= "user_name, user_pass";
			$sql .= ") VALUES (?, ?)";
			$stmt = $db_obj->db->prepare($sql);
			$stmt->bindValue(1, $user_name);
			$stmt->bindValue(2, sha1($user_pass));
			$stmt->execute();
			$stmt->closeCursor();
			$retval = true;
		}
		catch (PDOException $e)
		{
			UtilLog::writeLog($e->getMessage());
		}

		return $retval;
	}

	/**
	 * ユーザ情報を削除
	 * @param string $sid システムID
	 * @return bool
	 */
	public static function delUserInfo($sid)
	{
		$retval = false;

		try
		{
			$db_obj = new self();
			$sql = "DELETE FROM wol_user";
			$sql .= " WHERE sid = ?";
			$stmt = $db_obj->db->prepare($sql);
			$stmt->bindValue(1, $sid);
			$stmt->execute();
			$stmt->closeCursor();
			$retval = true;
		}
		catch (PDOException $e)
		{
			UtilLog::writeLog($e->getMessage());
		}

		return $retval;
	}

	/**
	 * デバイス一覧を取得
	 * @return array
	 */
	public static function getDeviceList()
	{
		$retval = array();

		try
		{
			$db_obj = new self();
			$sql = "SELECT mac_addr, device_name";
			$sql .= " FROM wol_device";
			$sql .= " ORDER BY device_name";
			$stmt = $db_obj->db->prepare($sql);
			$stmt->execute();
			while ($row = $stmt->fetch(PDO::FETCH_ASSOC))
			{
				$retval[$row['mac_addr']] = $row['device_name'];
			}
			$stmt->closeCursor();
		}
		catch (PDOException $e)
		{
			UtilLog::writeLog($e->getMessage());
		}

		return $retval;
	}

	/**
	 * デバイス情報を追加
	 * @param string $mac_addr MACアドレス文字列
	 * @param string $device_name デバイス名
	 * @return bool
	 */
	public static function addDeviceInfo($mac_addr, $device_name)
	{
		$retval = false;

		try
		{
			$db_obj = new self();
			$sql = "INSERT INTO wol_device (";
			$sql .= "mac_addr, device_name";
			$sql .= ") VALUES (?, ?)";
			$stmt = $db_obj->db->prepare($sql);
			$stmt->bindValue(1, $mac_addr);
			$stmt->bindValue(2, $device_name);
			$stmt->execute();
			$stmt->closeCursor();
			$retval = true;
		}
		catch (PDOException $e)
		{
			UtilLog::writeLog($e->getMessage());
		}

		return $retval;
	}

	/**
	 * デバイス情報を削除
	 * @param string $mac_addr MACアドレス文字列
	 * @return bool
	 */
	public static function delDeviceInfo($mac_addr)
	{
		$retval = false;

		try
		{
			$db_obj = new self();
			$sql = "DELETE FROM wol_device";
			$sql .= " WHERE mac_addr = ?";
			$stmt = $db_obj->db->prepare($sql);
			$stmt->bindValue(1, $mac_addr);
			$stmt->execute();
			$stmt->closeCursor();
			$retval = true;
		}
		catch (PDOException $e)
		{
			UtilLog::writeLog($e->getMessage());
		}

		return $retval;
	}

	/**
	 * ベンダー名を取得
	 * @param string $mac_addr MACアドレス文字列
	 * @return string
	 */
	public static function getVendorName($mac_addr)
	{
		$retval = '';
		$oui = strtoupper(substr(preg_replace('/[^a-z0-9]/i', '', $mac_addr), 0, 6));

		try
		{
			$db_obj = new self();
			$sql = "SELECT vendor_name FROM wol_vendor";
			$sql .= " WHERE mac_header = ?";
			$stmt = $db_obj->db->prepare($sql);
			$stmt->bindValue(1, $oui);
			$stmt->execute();
			$result = $stmt->fetchColumn();
			if ($result !== false)
				$retval = $result;
			$stmt->closeCursor();
		}
		catch (PDOException $e)
		{
			UtilLog::writeLog($e->getMessage());
		}

		return $retval;
	}

	/**
	 * ベンダー情報を更新
	 * @return bool
	 */
	public static function updateVendorInfo($is_download = false)
	{
		$retval = false;

		try
		{
			$db_obj = new self();

			// トランザクション開始
			$db_obj->db->beginTransaction();

			if ($is_download)
			{
				$oui_file = 'http://standards.ieee.org/develop/regauth/oui/oui.txt';
				$sql = "DELETE FROM wol_vendor";
				$stmt = $db_obj->db->prepare($sql);
				$stmt->execute();
				$stmt->closeCursor();
			}
			else
				$oui_file = INSTALL_PATH.'/libs/oui.txt';
			$oui_list = @file_get_contents($oui_file);
			if ($oui_list === FALSE)
				return false;
			$line = explode("\n", $oui_list);
			foreach($line as $item)
			{
				if (strpos($item, '(base 16)') !== false)
				{
					$oui = explode('     ', $item);
					$realitem = explode("\t\t", $item);
					$sql = "INSERT INTO wol_vendor (";
					$sql .= "mac_header, vendor_name";
					$sql .= ") VALUES (?, ?)";
					$stmt = $db_obj->db->prepare($sql);
					$stmt->bindValue(1, trim($oui[0]));
					$stmt->bindValue(2, $realitem[1]);
					$stmt->execute();
					$stmt->closeCursor();
				}
			}

			// コミット
			$db_obj->db->commit();

			$retval = true;
		}
		catch (PDOException $e)
		{
			UtilLog::writeLog($e->getMessage());
		}

		return $retval;
	}
}
?>