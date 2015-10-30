<?php
	/**
	 * WOL Manager - トップページ
	 */
	require_once 'functions.php';

	$INFORM_MSG = '';
	$ERROR_MSG = '';

	if (count($POST_DATA) > 0)
	{
		// ログイン認証
		if (isset($POST_DATA['btn_action']) && $POST_DATA['btn_action'] == "ログイン")
		{
			$USER_LIST = UtilSQLite::getUserList();
			if (count($USER_LIST) == 0)
			{
				// 初回アクセス時
				if (isset($POST_DATA['login_name']) && $POST_DATA['login_name'] != '' &&
						isset($POST_DATA['passwd']) && $POST_DATA['passwd'] != '')
				{
					if (!UtilSQLite::addUserInfo($POST_DATA['login_name'], $POST_DATA['passwd']))
						$INFORM_MSG = "「{$POST_DATA['login_name']}」の初回登録に失敗しました。";
					else
						$INFORM_MSG = "「{$POST_DATA['login_name']}」の初回登録に成功しました。再度ログインしてください。";
				}
				else
					$INFORM_MSG = '初回登録用のログイン情報を入力してください。';
			}
			else
			{
				// ユーザ検索
				$U_SID = UtilSQLite::getUserInfo($POST_DATA['login_name']);
				if ($U_SID != '')
				{
					// パスワードが正しいか
					if (sha1($POST_DATA['passwd']) === $USER_LIST[$U_SID]['user_pass'])
					{
						$SESS_DATA = array();
						$SESS_DATA['user_id']   = $U_SID;
						$SESS_DATA['user_name'] = $USER_LIST[$U_SID]['user_name'];
						$_SESSION['login_data'] = UtilString::buildQueryString($SESS_DATA);
						UtilLog::writeLog('ログイン成功：'.$U_SID, 'ACCESS');
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