<?php
	/**
	 * WOL Manager - 個別設定
	 */
	 // インストールパス
	define('INSTALL_PATH', dirname(__FILE__));
	// 出力ファイルパス
	define('HOME_URL', './');
	// ＤＢファイルパス
	define('DB_FILEPATH', dirname(INSTALL_PATH).'/db/wol.db');
	// ログファイルパス
	define('LOG_FILEPATH', dirname(INSTALL_PATH).'/log/');
	// PHPエラー出力範囲設定
	error_reporting(E_ALL ^ E_STRICT);
	// PHPエラーのログ出力を有効化
	ini_set('log_errors', 1);
	// PHPエラーログ出力パス
	ini_set('error_log', LOG_FILEPATH.'/php-error.log');

	// ライブラリのディレクトリをinclude_pathに追加
	$includes = array(INSTALL_PATH.'/classes', INSTALL_PATH.'/libs');
	$incPath = implode(PATH_SEPARATOR, $includes);
	set_include_path(get_include_path() . PATH_SEPARATOR . $incPath);
	require_once 'Smarty/Smarty.class.php';
	require_once 'wolLib.inc.php';
	setlocale(LC_ALL, 'ja_JP.UTF-8');
	spl_autoload_register(function ($className) {
		$file_name = preg_replace('/[^a-z_A-Z0-9]/u', '', $className) . '.php';
		require_once $file_name;
	});

	// 暗号化キー
	define('CRYPT_KEY', UtilSQLite::getCryptKey());
	// セッションのタイムアウト時間
	define('SESS_TIMEOUT', '+30 minutes');
	// JQueryMobileのテーマ
	define('JQM_DATA_THEME', 'b');
	// IPアドレス情報（CIDR形式）
	define('CIDR_INFO', '192.168.0.1/24');

	// Smartyのインスタンスを作成
	$SMARTY = new Smarty();
	// 各ディレクトリの指定
	$SMARTY->template_dir = INSTALL_PATH.'/templates/';
	$SMARTY->compile_dir = INSTALL_PATH.'/templates_c/';

	// セッション設定
	session_start();
	session_regenerate_id();

	// QUERY_STRINGの解析
	$GET_DATA = UtilString::parseQueryString($_SERVER['QUERY_STRING']);
	$POST_DATA = UtilString::getSanitizeData($_POST);
	$SESS_DATA = array();
	if (isset($_SESSION['login_data']) && $_SESSION['login_data'] != "")
		$SESS_DATA = UtilString::parseQueryString($_SESSION['login_data']);
?>
