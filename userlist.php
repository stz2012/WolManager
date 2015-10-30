<?php
	/**
	 * WOL Manager - ユーザ一覧
	 */
	require_once 'functions.php';

	if (isset($SESS_DATA['user_id']) && $SESS_DATA['user_id'] != '')
	{
		// ユーザ一覧取得
		$USER_LIST = UtilSQLite::getUserList();

		if (isset($GET_DATA['mode']))
		{
			switch ($GET_DATA['mode'])
			{
				// 追加フォーム
				case 'append_form':
					$SMARTY->assign('input_msg', "ユーザ情報を入力してください。");
					$param = array();
					$param['mode'] = 'append';
					$SMARTY->assign('form_param', UtilString::buildQueryString($param));
					break;

				// ユーザ追加
				case 'append':
					if (isset($POST_DATA['user_name']) && $POST_DATA['user_name'] != '' &&
							isset($POST_DATA['user_pass']) && $POST_DATA['user_pass'] != '')
					{
						$user_name = $POST_DATA['user_name'];
						$user_pass = $POST_DATA['user_pass'];
						if (!UtilSQLite::addUserInfo($user_name, $user_pass))
							$SMARTY->assign('inform_msg', "「{$user_name}」の登録に失敗しました。");
						else
							$SMARTY->assign('inform_msg', "「{$user_name}」の登録に成功しました。");
					}
					else
						$SMARTY->assign('inform_msg', 'ユーザ情報を正しく設定してください。');
					break;

				// 削除確認
				case 'delete_confirm':
					if (isset($GET_DATA['sid']) && $GET_DATA['sid'] != '')
					{
						$user_name = $USER_LIST[$GET_DATA['sid']]['user_name'];
						$SMARTY->assign('confirm_msg', "「{$user_name}」を削除しますか？");
						$param = array();
						$param['mode'] = 'delete';
						$param['sid'] = $GET_DATA['sid'];
						$SMARTY->assign('confirm_param', UtilString::buildQueryString($param));
					}
					break;

				// ユーザ削除
				case 'delete':
					if (isset($GET_DATA['sid']) && $GET_DATA['sid'] != '')
					{
						$user_name = $USER_LIST[$GET_DATA['sid']]['user_name'];
						if (!UtilSQLite::delUserInfo($GET_DATA['sid']))
							$SMARTY->assign('inform_msg', "「{$user_name}」の削除に失敗しました。");
						else
							$SMARTY->assign('inform_msg', "「{$user_name}」の削除に成功しました。");
					}
					break;
			}
		}

		// ユーザリスト作成
		$res_data = array();
		foreach ($USER_LIST as $user_key => $user_val)
		{
			$param = array();
			$param['mode'] = 'delete_confirm';
			$param['sid']  = $user_key;
			$res_data[] = array(
				'user_name'    => $user_val['user_name'],
				'delete_param' => UtilString::buildQueryString($param)
			);
		}

		// Smarty処理
		$SMARTY->assign('home_url', HOME_URL);
		$SMARTY->assign('res_data', $res_data);
		$SMARTY->assign('sess_data', $SESS_DATA);
		$SMARTY->assign('append_param', UtilString::buildQueryString(array('mode' => 'append_form')));
		$SMARTY->display('userlist.html');
	}
	else
	{
		header("Location: index.php");
		exit;
	}
?>