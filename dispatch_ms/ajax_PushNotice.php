<?php

//session_start();

//$memberID = $_SESSION['memberID'];
//$powerkey = $_SESSION['powerkey'];


$site_db = $_POST['site_db'];
$web_id = $_POST['web_id'];
$project_id = $_POST['project_id'];
$auth_id = $_POST['auth_id'];
$from_id = $_POST['from_id'];
$tb = $_POST['tb'];
$dispatch_id = $_POST['dispatch_id'];
$PushContent = $_POST['PushContent'];

//載入公用函數
@include_once '/website/include/pub_function.php';

@include_once '/website/include/gcm_function.php';

@include_once("/website/class/".$site_db."_info_class.php");



$HTTPS_HOST = isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '');
$HTTPS_HOST = "https://".$HTTPS_HOST;


$member_icon = $HTTPS_HOST.getmemberpict50($from_id);

$member_row = getkeyvalue2("memberinfo","member","member_no = '$from_id'","member_name");
$member_name = $member_row['member_name'];


$big_picture = "";

$now = date('Y-m-d  H:i');


$dispatch_row = getkeyvalue2($site_db."_info","dispatch","dispatch_id = '$dispatch_id'","dispatch_date");
$dispatch_date = $dispatch_row['dispatch_date'];


$title = "派工作業";
$caption = "出工任務內容";
$dispatch_desc = "日期：".$dispatch_date." (#".$dispatch_id.")";
$message = htmlspecialchars_decode($PushContent, ENT_QUOTES);

//$url = "/index.php?ch=view&dispatch_id=".$dispatch_id."&project_id=".$project_id."&auth_id=".$auth_id."&fm=".$tb."#myScrollspy";
$url = "/index.php?ch=view&pjt=派工作業&dispatch_id=".$dispatch_id."&project_id=".$project_id."&auth_id=".$auth_id."&fm=".$tb."#myScrollspy";

$mynotices_message = $title."<div class=\"mytable\"><div class=\"myrow\"><div class=\"mycell w-auto px-1\"><div><div class=\"size12 weight\">".$member_name." 於 <span class=\"red\">".$now."</span> 發出了通知訊息</div></div><div style=\"padding: 0 3px 3px 0;\"><div class=\"size12 blue weight\">".$caption."</div><div class=\"size12 weight\">".$dispatch_desc."</div><div class=\"block-with-text size12\" style=\"max-height: 7.2em;\">".$message."</div></div></div></div></div>";


//取得發送對象人員名單(指定管理人)
$processing_staff = array();

$mDB = "";
$mDB = new MywebDB();

$Qry = "SELECT member_no FROM pjmyfellow
WHERE web_id = '$web_id' and project_id = '$project_id' and auth_id = '$auth_id' and pro_id = 'squadleader'";
$mDB->query($Qry);
if ($mDB->rowCount() > 0) {
	while ($row=$mDB->fetchRow(2)) {
		$member_no = $row['member_no'];

		if (!empty($member_no)) {
			if (!in_array($member_no,$processing_staff))
				$processing_staff[] = $member_no;
		}
	}
}


//$processing_staff[] = "apupu";

$to_ok = array();
foreach($processing_staff as $to) {
	
	//if ($to <> $memberID) {
	
		if (!empty($to)) {
			//再檢查非空白
			$to_ok[] = $to;
			$Qry = "insert into mynotices (web_id,project_id,member_no,from_id,ntype,content,url,last_time)
			values ('$web_id','$project_id','$to','$from_id','DP','$mynotices_message','$url',now())";
			$mDB->query($Qry);

		}

	//}
	
}


//更新 dispatch
$Qry="UPDATE dispatch set
	notify_times = notify_times+1
	,last_webpush = now()
	where dispatch_id = '$dispatch_id'";
$mDB->query($Qry);


$mDB->remove();


$WebPushContent = $member_name." 於 ".$now." 發出了通知訊息".PHP_EOL.$caption.PHP_EOL.$dispatch_desc.PHP_EOL.$message;

$web_retval = web_push($site_db,$web_id,$to_ok,$title,$WebPushContent,$member_icon,$big_picture,$url);


echo json_encode($to_ok);

?>