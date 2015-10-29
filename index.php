<?php
	/**
	 * WOL Manager - トップページ
	 */
	require_once 'functions.php';

	$INFORM_MSG = "";
	$ERROR_MSG = "";

	if (count($POST_DATA) > 0)
	{
		// ログイン認証
		if (isset($POST_DATA['btn_action']) && $POST_DATA['btn_action'] == "ログイン")
		{
			// ユーザ検索
			$USER_INFO = UtilSQLite::getUserInfo($POST_DATA['login_name']);
			if (count($USER_INFO) > 0)
			{
				// パスワードが正しいか
				if (sha1($POST_DATA['passwd']) === $USER_INFO['user_pass'])
				{
					$SESS_DATA = array();
					$SESS_DATA['user_id']   = $USER_INFO['sid'];
					$SESS_DATA['user_name'] = $USER_INFO['user_name'];
					$_SESSION['login_data'] = UtilString::buildQueryString($SESS_DATA);
					UtilLog::writeLog('ログイン成功：'.$SESS_DATA['user_id'], 'ACCESS');
					header("Location: menu.php");
					exit;
				}
				else
				{
					$ERROR_MSG = "ログイン名、または、パスワードが違います。";
					UtilLog::writeLog('ログイン失敗：'.print_r($POST_DATA, true), 'ACCESS');
				}
			}
			else
			{
				$ERROR_MSG = "ログイン名、または、パスワードが違います。";
				UtilLog::writeLog('不正ユーザ：'.print_r($POST_DATA, true), 'ACCESS');
			}
		}
		// ログアウト
		if (isset($POST_DATA['btn_action']) && $POST_DATA['btn_action'] == "ログアウト")
		{
			$SESS_DATA = array();
			$_SESSION['login_data'] = "";
			unset($_SESSION['login_data']);
			UtilLog::writeLog('ログアウト', 'ACCESS');
			header("Location: {$_SERVER['SCRIPT_NAME']}");
			exit;
		}
	}

	// Smarty処理
	$SMARTY->assign('home_url',   HOME_URL);
	$SMARTY->assign('sess_data',  $SESS_DATA);
	$SMARTY->assign('post_data',  $POST_DATA);
	$SMARTY->assign('inform_msg', $INFORM_MSG);
	$SMARTY->assign('error_msg',  $ERROR_MSG);
	$SMARTY->display('index.html');
?>