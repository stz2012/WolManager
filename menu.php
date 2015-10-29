<?php
	/**
	 * WOL Manager - メニュー
	 */
	require_once 'functions.php';

	// ログイン時のみ表示
	if (isset($SESS_DATA['user_id']) && $SESS_DATA['user_id'] != '')
	{
		// デバイス一覧取得
		$DEV_LIST = UtilSQLite::getDeviceList();

		if (isset($GET_DATA['mode']) && isset($GET_DATA['mac_addr']) && $GET_DATA['mac_addr'] != '')
		{
			switch ($GET_DATA['mode'])
			{
				// 起動確認
				case 'wake_confirm':
					if (isset($GET_DATA['ip_addr']) && $GET_DATA['ip_addr'] != '')
						$device_name = $GET_DATA['ip_addr'];
					else
						$device_name = $DEV_LIST[$GET_DATA['mac_addr']];
					$SMARTY->assign('confirm_msg', "「{$device_name}」を起動しますか？");
					$param = array();
					$param['mode'] = 'wake';
					$param['mac_addr'] = $GET_DATA['mac_addr'];
					if (isset($GET_DATA['ip_addr']) && $GET_DATA['ip_addr'] != '')
						$param['ip_addr'] = $GET_DATA['ip_addr'];
					$SMARTY->assign('confirm_param', UtilString::buildQueryString($param));
					break;

				// デバイス起動
				case 'wake':
					if (isset($GET_DATA['ip_addr']) && $GET_DATA['ip_addr'] != '')
						$device_name = $GET_DATA['ip_addr'];
					else
						$device_name = $DEV_LIST[$GET_DATA['mac_addr']];
					UtilLog::writeLog('WOLパケットを送信 Name:'.$device_name.' MAC:'.$GET_DATA['mac_addr'], 'ACCESS');
					$status = WakeOnLan(WAKE_IP, $GET_DATA['mac_addr']);
					if ( $status === TRUE )
						$SMARTY->assign('inform_msg', "「{$device_name}」の起動に成功しました。");
					else
						$SMARTY->assign('inform_msg', "「{$device_name}」の起動に失敗しました。");
					break;

				// 追加フォーム
				case 'append_form':
					$SMARTY->assign('input_msg', "デバイス名を入力してください。");
					$param = array();
					$param['mode'] = 'append';
					$param['mac_addr'] = $GET_DATA['mac_addr'];
					$SMARTY->assign('form_param', UtilString::buildQueryString($param));
					break;

				// デバイス追加
				case 'append':
					if (isset($POST_DATA['device_name']) && $POST_DATA['device_name'] != '')
					{
						$device_name = $POST_DATA['device_name'];
						$status = UtilSQLite::addDeviceInfo($GET_DATA['mac_addr'], $device_name);
						if ( $status === TRUE )
							$SMARTY->assign('inform_msg', "「{$device_name}」の登録に成功しました。");
						else
							$SMARTY->assign('inform_msg', "「{$device_name}」の登録に失敗しました。");
					}
					break;

				// 削除確認
				case 'delete_confirm':
					$device_name = $DEV_LIST[$GET_DATA['mac_addr']];
					$SMARTY->assign('confirm_msg', "「{$device_name}」を削除しますか？");
					$param = array();
					$param['mode'] = 'delete';
					$param['mac_addr'] = $GET_DATA['mac_addr'];
					$SMARTY->assign('confirm_param', UtilString::buildQueryString($param));
					break;

				// デバイス削除
				case 'delete':
					$device_name = $DEV_LIST[$GET_DATA['mac_addr']];
					$status = UtilSQLite::delDeviceInfo($GET_DATA['mac_addr']);
					if ( $status === TRUE )
						$SMARTY->assign('inform_msg', "「{$device_name}」の削除に成功しました。");
					else
						$SMARTY->assign('inform_msg', "「{$device_name}」の削除に失敗しました。");
					break;

				// ベンダー情報更新
				case 'update_vendor':
					$status = UtilSQLite::updateVendorInfo(true);
					if ( $status === TRUE )
						$SMARTY->assign('inform_msg', "ベンダー情報の更新に成功しました。");
					else
						$SMARTY->assign('inform_msg', "ベンダー情報の更新に失敗しました。");
					break;
			}
		}

		// 登録済みリスト作成
		$res_data1 = array();
		foreach ($DEV_LIST as $device_key => $device_val)
		{
			$param = array();
			$param['mode'] = 'wake_confirm';
			$param['mac_addr'] = $device_key;
			$tmp_str1 = UtilString::buildQueryString($param);
			$param['mode'] = 'delete_confirm';
			$tmp_str2 = UtilString::buildQueryString($param);
			$res_data1[] = array(
				'mac_addr'      => $device_key,
				'device_name'   => $device_val,
				'vendor_name'   => UtilSQLite::getVendorName($device_key),
				'confirm_param' => $tmp_str1,
				'delete_param'  => $tmp_str2
			);
		}

		// 未登録リスト作成
		$res_data2 = array();
		$arplist = GetArpInfo();
		foreach ($arplist as $entry)
		{
			// 登録済みの場合、読み飛ばす
			if (in_array($entry['mac_addr'], array_keys($DEV_LIST)))
				continue;
			$param = array();
			$param['mode'] = 'wake_confirm';
			$param['mac_addr'] = $entry['mac_addr'];
			$param['ip_addr'] = $entry['ip_addr'];
			$tmp_str1 = UtilString::buildQueryString($param);
			$param['mode'] = 'append_form';
			$tmp_str2 = UtilString::buildQueryString($param);
			$res_data2[] = array(
				'ip_addr'       => $entry['ip_addr'],
				'mac_addr'      => $entry['mac_addr'],
				'vendor_name'   => UtilSQLite::getVendorName($entry['mac_addr']),
				'confirm_param' => $tmp_str1,
				'append_param'  => $tmp_str2
			);
		}

		// Smarty処理
		$SMARTY->assign('home_url',     HOME_URL);
		$SMARTY->assign('res_data1',    $res_data1);
		$SMARTY->assign('res_data2',    $res_data2);
		$SMARTY->assign('sess_data',    $SESS_DATA);
		$SMARTY->assign('update_param', UtilString::buildQueryString(array(
			'mode'     => 'update_vendor',
			'mac_addr' => 'dummy'
		)));
		$SMARTY->display('menu.html');
	}
	else
	{
		header("Location: index.php");
		exit;
	}
?>