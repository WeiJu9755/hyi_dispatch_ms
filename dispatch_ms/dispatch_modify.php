<?php

//error_reporting(E_ALL); 
//ini_set('display_errors', '1');

session_start();

$memberID = $_SESSION['memberID'];
$powerkey = $_SESSION['powerkey'];


//計算請假時間
function calculateLeaveHours($startTime, $endTime) {
    // 設定工作時間的起點與終點
    $workStartTime = new DateTime('08:00');
    $workEndTime = new DateTime('17:00');
    
    // 中午休息時間
    $lunchStartTime = new DateTime('12:00');
    $lunchEndTime = new DateTime('13:00');

    // 將請假開始時間與結束時間轉為 DateTime 物件
    $start = new DateTime($startTime);
    $end = new DateTime($endTime);

    // 檢查是否在工作時間範圍內
    if ($start < $workStartTime) $start = $workStartTime;
    if ($end > $workEndTime) $end = $workEndTime;

    // 計算總請假時數（包含中午時間）
    $interval = $start->diff($end);
    $leaveHours = $interval->h + ($interval->i / 60);

    // 檢查是否跨越中午休息時間，並扣除 1 小時
    if ($start < $lunchEndTime && $end > $lunchStartTime) {
        $leaveHours -= 1; // 扣除 1 小時中午休息時間
    }

    // 將請假時間以半小時為單位計算
    $leaveHours = ceil($leaveHours * 2) / 2;

    return $leaveHours;
}


require_once '/website/os/Mobile-Detect-2.8.34/Mobile_Detect.php';
$detect = new Mobile_Detect;


//載入公用函數
@include_once '/website/include/pub_function.php';

//連結資料
@include_once("/website/class/".$site_db."_info_class.php");

/* 使用xajax */
@include_once '/website/xajax/xajax_core/xajax.inc.php';
$xajax = new xajax();

$xajax->registerFunction("processform");
function processform($aFormValues){

	$objResponse = new xajaxResponse();
	
	$web_id				= trim($aFormValues['web_id']);
	$project_id			= trim($aFormValues['project_id']);
	$auth_id			= trim($aFormValues['auth_id']);

	$dispatch_id		= trim($aFormValues['dispatch_id']);
	$memberID			= trim($aFormValues['memberID']);
	
	SaveValue($aFormValues);
	
	$objResponse->script("setSave();");
	$objResponse->script("updispatch();");
	$objResponse->script("parent.myDraw();");

	//$objResponse->script("art.dialog.tips('已存檔!',1);");
	$objResponse->script("parent.$.fancybox.close();");
		
	
	return $objResponse;
}


$xajax->registerFunction("SaveValue");
function SaveValue($aFormValues){

	$objResponse = new xajaxResponse();
	
		//進行存檔動作
		$site_db			= trim($aFormValues['site_db']);
		$web_id				= trim($aFormValues['web_id']);
		$auto_seq			= trim($aFormValues['auto_seq']);
		$content			= trim($aFormValues['content']);
		$task_safety_tips		= trim($aFormValues['task_safety_tips']);
		$todolist				= trim($aFormValues['todolist']);
		$posted					= trim($aFormValues['posted']);

		$dispatch_id		= trim($aFormValues['dispatch_id']);
		$memberID			= trim($aFormValues['memberID']);
		
		//更新
		$mDB = "";
		$mDB = new MywebDB();
		
		$Qry="UPDATE dispatch set
				content = '$content'
				,task_safety_tips	= '$task_safety_tips'
				,todolist			= '$todolist'
				,posted				= '$posted'
				,makeby = '$memberID'
				,last_modify = now()
				where auto_seq = '$auto_seq'";
				
		$mDB->query($Qry);
        $mDB->remove();

		//update_dispatch($dispatch_id,$memberID);

		$objResponse->script("$('#myConfirmSending').prop('disabled', true);");
		
	return $objResponse;
}

//送出前讓系統再檢查乙次
$xajax->registerFunction("Checkall");
function Checkall($aFormValues){

	$objResponse = new xajaxResponse();
	
	$web_id				= trim($aFormValues['web_id']);
	$auto_seq			= trim($aFormValues['auto_seq']);
	$project_id			= trim($aFormValues['project_id']);
	$auth_id			= trim($aFormValues['auth_id']);
	$dispatch_id		= trim($aFormValues['dispatch_id']);

	$warning_list = "";

	$mDB = "";
	$mDB = new MywebDB();

	$mDB2 = "";
	$mDB2 = new MywebDB();
	
	//檢查是否有合約工項/派工資料
	$Qry="SELECT a.*,b.work_project as contract_work_project FROM dispatch_contract_details a
		LEFT JOIN contract_details b ON b.contract_id = a.contract_id AND b.seq = a.seq
		WHERE a.dispatch_id = '$dispatch_id'
		ORDER BY a.auto_seq";
	$mDB->query($Qry);
	if ($mDB->rowCount() > 0) {
		while ($row=$mDB->fetchRow(2)) {

			$contract_id = $row['contract_id'];
			$seq = $row['seq'];
			$work_project = $row['contract_work_project'];
			$actual_qty = $row['actual_qty'];

			if ($actual_qty <= 0) {
				$warning_list .= "<div class=\"w-100\">合約項次 $seq $work_project 實際數不可小或等於0</div>";
			}

			//檢查是否有人員派工
			$Qry2="SELECT * FROM dispatch_attendance_sub
				WHERE dispatch_id = '$dispatch_id' AND contract_id = '$contract_id' AND seq = '$seq'
				ORDER BY auto_seq";
			$mDB2->query($Qry2);
			if ($mDB2->rowCount() <= 0) {
				$warning_list .= "<div class=\"w-100\">合約項次 $seq $work_project 沒有人員派工資料</div>";
			}


		}
	} else {

		$warning_list .= "<div class=\"w-100\">沒有任何合約工項/派工資料</div>";

	}

	/*
	//檢查是否有使用 物料名稱/使用機具
	$Qry="SELECT * FROM dispatch_contract_details
		WHERE dispatch_id = '$dispatch_id'
		ORDER BY auto_seq";
	$mDB->query($Qry);

	$manpower_total2 = 0;
	$attendance_day_total2 = 0;

	if ($mDB->rowCount() > 0) {
	} else {
		$warning_list .= "<div class=\"w-100\">沒有任何工作項目資料</div>";
	}
	*/

	


	$mDB2->remove();
	$mDB->remove();

	//更新
	if (!empty($warning_list)) {

		$objResponse->assign("warning_list".$auto_seq,"innerHTML",$warning_list);

		$objResponse->script("jAlert('警示', '仍有需處理的檢查事項', 'red', '', 2000);");

		$objResponse->script("$('#myConfirmSending').prop('disabled', true);");

	} else {

		$objResponse->assign("warning_list".$auto_seq,"innerHTML","");
		$objResponse->script("jAlert('警示', '目前看來資料應沒問題了，不過仍請您再自行確認，謝謝', 'green', '', 2000);");

		$objResponse->script("$('#myConfirmSending').prop('disabled', false);");


		//ConfirmSending($aFormValues);
	
	}

		
	
	return $objResponse;
}

$xajax->registerFunction("ConfirmSending");
function ConfirmSending($aFormValues){

	$objResponse = new xajaxResponse();
	
	$web_id				= trim($aFormValues['web_id']);
	$auto_seq			= trim($aFormValues['auto_seq']);
	$project_id			= trim($aFormValues['project_id']);
	$auth_id			= trim($aFormValues['auth_id']);
	$dispatch_id		= trim($aFormValues['dispatch_id']);
	$employee_id		= trim($aFormValues['employee_id']);

	$warning_list = "";

	$mDB = "";
	$mDB = new MywebDB();

	$mDB2 = "";
	$mDB2 = new MywebDB();

	/*
	//將出工名單的每個員工的工時寫入至 dispatch_wt_month
	$Qry="SELECT a.employee_id,a.attendance_day,b.company_id,b.team_id as dispatch_team_id,YEAR(b.dispatch_date) AS dispatch_year,MONTH(b.dispatch_date) AS dispatch_month,DAY(b.dispatch_date) AS dispatch_day FROM dispatch_member a
	LEFT JOIN dispatch b ON b.dispatch_id = a.dispatch_id
	WHERE a.dispatch_id = '$dispatch_id'
	ORDER BY a.dispatch_id,a.employee_id";
	$mDB->query($Qry);
	if ($mDB->rowCount() > 0) {
		while ($row=$mDB->fetchRow(2)) {
			$dispatch_year = $row['dispatch_year'];
			$dispatch_month = $row['dispatch_month'];
			$dispatch_day = (int)$row['dispatch_day'];
			$company_id = $row['company_id'];
			$dispatch_team_id = $row['dispatch_team_id'];
			$employee_id = $row['employee_id'];
			$attendance_day = round($row['attendance_day']/8,4);

			//更新至 dispatch_wt_month
			//先檢查員工資料是否已存在
			$Qry2="SELECT * FROM dispatch_wt_month 
				WHERE dispatch_year = '$dispatch_year' AND dispatch_month = '$dispatch_month' AND company_id = '$company_id' AND team_id = '$dispatch_team_id' AND employee_id = '$employee_id'";
			$mDB2->query($Qry2);
			if ($mDB2->rowCount() > 0) {
				//已存在則進行更新
				$wt = "wt_".$dispatch_day;
				$Qry2="UPDATE dispatch_wt_month set ".$wt." = '$attendance_day'
					WHERE dispatch_year = '$dispatch_year' AND dispatch_month = '$dispatch_month' AND company_id = '$company_id' AND team_id = '$dispatch_team_id' AND employee_id = '$employee_id'";
				$mDB2->query($Qry2);
			} else {
				//不存在則進行新增
				$wt = "wt_".$dispatch_day;
				$Qry2="INSERT INTO dispatch_wt_month (dispatch_year,dispatch_month,company_id,team_id,employee_id,".$wt.") 
					VALUES ('$dispatch_year','$dispatch_month','$company_id','$dispatch_team_id','$employee_id','$attendance_day')";
				$mDB2->query($Qry2);
			}
		}
	}
	*/

	//產生 物料/使用機具 之出庫單作業
	//產生主檔資料
	//自動產生 stock_out_id
	$today = date("Ymd");

	//取得最後代號
	$Qry = "SELECT stock_out_id FROM stock_out WHERE SUBSTRING(stock_out_id,3,8) = '$today' ORDER BY stock_out_id DESC LIMIT 0,1";
	$mDB->query($Qry);
	if ($mDB->rowCount() > 0) {
		$row=$mDB->fetchRow(2);
		$temp_stock_out_id = $row['stock_out_id'];
		$str4 = substr($temp_stock_out_id,-4,4);
		$num = (int)$str4+1;
		$filled_int = sprintf("%04d", $num);
		$new_stock_out_id = "SO".$today.$filled_int;
	} else {
		$new_stock_out_id = "SO".$today."0001";
	}

	$stock_out_date = date("Y-m-d");
	$stock_out_type = "工程用料";

	//新增出庫單主檔
	$Qry="insert into stock_out (stock_out_id,stock_out_date,stock_out_type,handler_id,status,create_date,last_modify) values ('$new_stock_out_id','$stock_out_date','$stock_out_type','$employee_id','已出庫',now(),now())";
	$mDB->query($Qry);

	//產生明細子檔資料
	$Qry="SELECT * FROM dispatch_material_details
	where dispatch_id = '$dispatch_id'
	order by auto_seq";
	$mDB->query($Qry);
	if ($mDB->rowCount() > 0) {
		while ($row=$mDB->fetchRow(2)) {
			$material_no = $row['material_no'];
			$warehouse = $row['warehouse'];
			$stock_out_qty = $row['stock_out_qty'];
			$remarks = $row['remarks'];

			//新增出庫單子檔
			$Qry2="INSERT INTO stock_out_detail (stock_out_id,material_no,warehouse,stock_out_qty,remarks,last_modify) values ('$new_stock_out_id','$material_no','$warehouse','$stock_out_qty','$remarks',now())";
			$mDB2->query($Qry2);

			//更新倉庫子檔
			$Qry2="UPDATE inventory_sub SET
					stock_qty			= stock_qty - '$stock_out_qty'
					,last_modify		= NOW()
					WHERE material_no = '$material_no' AND warehouse = '$warehouse'";
			$mDB2->query($Qry2);

			//更新庫存料件主檔總庫存量
			$Qry2="UPDATE inventory set
					stock_qty = (SELECT SUM(stock_qty) FROM inventory_sub WHERE material_no = '$material_no')
					,last_modify		= now()
					where material_no = '$material_no'";
			$mDB2->query($Qry2);


		}
	}

	//更新主檔狀態
	$Qry="UPDATE dispatch set
			ConfirmSending	= 'Y'
			,ConfirmSending_datetime	= now()
			where auto_seq = '$auto_seq'";
			
	$mDB->query($Qry);

	$mDB2->remove();
	$mDB->remove();

	$objResponse->script("parent.myDraw();");
	$objResponse->script("parent.$.fancybox.close();");

	
	return $objResponse;
}

//還原確認
$xajax->registerFunction("Reduction");
function Reduction($aFormValues){

	$objResponse = new xajaxResponse();
	
	$web_id				= trim($aFormValues['web_id']);
	$auto_seq			= trim($aFormValues['auto_seq']);
	$project_id			= trim($aFormValues['project_id']);
	$auth_id			= trim($aFormValues['auth_id']);
	$dispatch_id		= trim($aFormValues['dispatch_id']);
	
	//更新
	$mDB = "";
	$mDB = new MywebDB();
	
	$Qry="UPDATE dispatch set
			ConfirmSending = 'N'
			,ConfirmSending_datetime = null
			,last_modify = now()
			where auto_seq = '$auto_seq'";
			
	$mDB->query($Qry);
	$mDB->remove();


	$objResponse->script("parent.myDraw();");

	$objResponse->script("parent.$.fancybox.close();");
		
	
	return $objResponse;
}

$xajax->registerFunction("Contract_DetailsDeleteRow");
function Contract_DetailsDeleteRow($auto_seq,$dispatch_id,$contract_id,$seq){

	$objResponse = new xajaxResponse();
	
	$mDB = "";
	$mDB = new MywebDB();

	//刪除主資料
	$Qry="delete from dispatch_attendance_sub where dispatch_id = '$dispatch_id' and contract_id = '$contract_id' and seq = '$seq'";
	$mDB->query($Qry);
	
	$Qry="delete from dispatch_contract_details where auto_seq = '$auto_seq'";
	$mDB->query($Qry);

	$mDB->remove();
	
    $objResponse->script("dispatch_contract_details_myDraw();");

	$objResponse->script("$('#myConfirmSending').prop('disabled', true);");

	return $objResponse;
	
}

$xajax->registerFunction("dispatch_material_details_DeleteRow");
function dispatch_material_details_DeleteRow($auto_seq){

	$objResponse = new xajaxResponse();
	
	$mDB = "";
	$mDB = new MywebDB();

	//刪除主資料
	$Qry="delete from dispatch_material_details where auto_seq = '$auto_seq'";
	$mDB->query($Qry);

	$mDB->remove();
	
    $objResponse->script("dispatch_material_details_myDraw();");

	$objResponse->script("$('#myConfirmSending').prop('disabled', true);");

	return $objResponse;
	
}

$xajax->registerFunction("returnValue");
function returnValue($auto_seq,$dispatch_id,$contract_id,$seq){
	$objResponse = new xajaxResponse();

	
	//取得出勤狀況
	$mDB = "";
	$mDB = new MywebDB();

	$Qry="SELECT a.*,b.employee_name FROM dispatch_attendance_sub a
	LEFT JOIN employee b ON b.employee_id = a.employee_id
	WHERE a.dispatch_id = '$dispatch_id' AND a.contract_id = '$contract_id' AND a.seq = '$seq'
	order by a.auto_seq
	";

	$mDB->query($Qry);

	$attendance_list = "";

	if ($mDB->rowCount() > 0) {

$attendance_list=<<<EOT
<div class="mytable w-100">
EOT;
		while ($row=$mDB->fetchRow(2)) {
			$employee_id = $row['employee_id'];
			$employee_name = $row['employee_name'];
			$attendance_hours = $row['attendance_hours'];
			$attendance_remark = $row['attendance_remark'];
			$is_overtime = $row['is_overtime'];
			$m_is_overtime = "";
			if ($is_overtime == "Y")
				$m_is_overtime = "加班";

			if (!empty($row['attendance_start']))
				$attendance_start = date("H:i",strtotime($row['attendance_start']));
			else
				$attendance_start = "";

			if (!empty($row['attendance_end']))
				$attendance_end = date("H:i",strtotime($row['attendance_end']));
			else
				$attendance_end = "";



			if (($attendance_start == "") || ($attendance_start == "00:00")) {
				$attendance_start = "";
			}

			if (($attendance_end == "") || ($attendance_end == "00:00")) {
				$attendance_end = "";
			}


$attendance_list.=<<<EOT
	<div class="myrow">
		<div class="mycell text-nowrap" style="width:15%;">
			<div class="inline size12 weight">$employee_name</div>
		</div>
		<div class="mycell" style="width:15%;">
			<div class="size12 text-nowrap">$attendance_start ~ $attendance_end</div>
		</div>
		<div class="mycell text-center" style="width:5%;">
			<div class="size12">$attendance_hours</div>
		</div>
		<div class="mycell" style="width:50%;">
			<div class="size12">$attendance_remark</div>
		</div>
		<div class="mycell text-center" style="width:5%;">
			<div class="size12 text-nowrap">$m_is_overtime</div>
		</div>
	</div>
EOT;


		}
$attendance_list.=<<<EOT
</div>
EOT;
	}

	$mDB->remove();

	$objResponse->assign("attendance_list".$auto_seq,"innerHTML",$attendance_list);

    return $objResponse;
}

$xajax->processRequest();


$fm = $_GET['fm'];
$auto_seq = $_GET['auto_seq'];

$CanDo = true;

if (isset($_GET['readonly'])) {
	if ($_GET['readonly'] == "Y") {
		$CanDo = false;
	}
}

$disabled = "";
if ($CanDo <> true){
	$disabled = "disabled";
}



$mess_title = $title;


//取得預設值
$settings_row = getkeyvalue2($site_db."_info","settings","auto_seq = '1'","def_attendance_end");
if (!empty($settings_row['def_attendance_end']))
	$def_attendance_end = $settings_row['def_attendance_end'];
else
	$def_attendance_end = "";


$mDB = "";
$mDB = new MywebDB();
$Qry="SELECT a.*,b.caption,c.company_name,c.short_name,d.contract_caption,d.contract_abbreviation FROM dispatch a
LEFT JOIN projectitem b ON b.project_id = a.project_id AND b.auth_id = a.auth_id
LEFT JOIN company c ON c.company_id = a.company_id
LEFT JOIN contract d ON d.contract_id = a.contract_id
WHERE a.auto_seq = '$auto_seq'";
$mDB->query($Qry);
$total = $mDB->rowCount();
if ($total > 0) {
    //已找到符合資料
	$row=$mDB->fetchRow(2);
	$project_id = $row['project_id'];
	$auth_id = $row['auth_id'];
	$caption = $row['caption'];
	$main_class = $row['main_class'];
	$small_class = $row['small_class'];
	$dispatch_id = $row['dispatch_id'];
	$dispatch_date = $row['dispatch_date'];
	$dispatch_type = $row['dispatch_type'];
	$company_id = $row['company_id'];
	$company_name = $row['company_name'];
	$short_name = $row['short_name'];
	$contract_id = $row['contract_id'];
	$contract_caption = $row['contract_caption'];
	$contract_abbreviation = $row['contract_abbreviation'];
	$team_id = $row['team_id'];
	$content = $row['content'];
	$task_safety_tips = $row['task_safety_tips'];
	$todolist = $row['todolist'];
	$posted = $row['posted'];
	$makeby = $row['makeby'];
	$ConfirmSending = $row['ConfirmSending'];
	$ConfirmSending_datetime = $row['ConfirmSending_datetime'];

	$notify_times = $row['notify_times'];
	$last_webpush = $row['last_webpush'];

	$contract = "";
	if (!empty(contract_abbreviation)) {
		$contract = $contract_abbreviation;
	} else {
		$contract = $contract_caption;
	}

	if ($posted=="Y")
	  $m_posted = "checked=\"checked\"";


}

if (!empty($short_name)) {
	$show_company_name = $short_name;
} else {
	$show_company_name = $company_name;
}

$show_contract = '<div class="inline text-nowrap me-2">'.$contract.'</div><div class="inline size08 gray">'.$contract_id.'</div>';


$tb = "dispatch";
//計算圖檔數量
$Qry="select file_id,caption,orderby from pjfiles_caption where web_id = '$web_id' and project_id = '$project_id' and auth_id = '$auth_id' and ftype = '$tb' and localpath = 'attach' and seq = '$auto_seq' order by orderby";
$mDB->query($Qry);
$files_list = "";
$n = 0;
$file_size_total = 0;

if ($mDB->rowCount() > 0) {
	while ($row=$mDB->fetchRow(2)) {
		$o_file = $row['file_id'];
		$file_size = filesize($o_file);
		$file_size_total += $file_size;
		$n++;
	}
}

$mDB->remove();

$show_file_size_total = "<span style=\"white-space: pre;\">(".byteConvert($file_size_total).")</span>";

if ($n > 0)
	$show_files_total = "<i class=\"bi bi-file-earmark-medical blue01 me-1\" title=\"附加檔案\"></i><span class=\"badge text-bg-info me-1\">$n</span><span class=\"red weight me-2\">".$show_file_size_total."</span>";
else 
	$show_files_total = "";



$mDB->remove();



$show_savebtn=<<<EOT
<div class="btn-group vbottom" role="group" style="margin-top:5px;">
	<button $disabled id="save" class="btn btn-primary" type="button" onclick="CheckValue(this.form);" style="padding: 5px 15px;"><i class="bi bi-check-circle"></i>&nbsp;存檔</button>
	<button $disabled id="cancel" class="btn btn-secondary display_none" type="button" onclick="setCancel();" style="padding: 5px 15px;"><i class="bi bi-x-circle"></i>&nbsp;取消</button>
	<button id="close" class="btn btn-danger" type="button" onclick="parent.myDraw();parent.$.fancybox.close();" style="padding: 5px 15px;"><i class="bi bi-power"></i>&nbsp;關閉</button>
</div>
EOT;


//取得使用者員工身份
$member_picture = getmemberpict50($makeby);

$member_row = getkeyvalue2("memberinfo","member","member_no = '$makeby'","member_name");
$member_name = $member_row['member_name'];

$employee_row = getkeyvalue2($site_db."_info","employee","member_no = '$makeby'","count(*) as manager_count,employee_id,employee_name,employee_type");
$manager_count =$employee_row['manager_count'];
if ($manager_count > 0) {
	$employee_id = $employee_row['employee_id'];
	$employee_name = $employee_row['employee_name'];
	$employee_type = $employee_row['employee_type'];
} else {
	$employee_id = $makeby;
	$employee_name = $member_name;
	$employee_type = "未在員工名單";
}

$member_logo=<<<EOT
<div class="float-end text-nowrap mt-3 size14 weight">
	<div class="inline mytable bg-white rounded">
		<div class="myrow">
			<div class="mycell text-center text-nowrap">
				<div class="inline me-1">By：</div>
				<img src="$member_picture" height="32" class="inline rounded">
			</div>
			<div class="mycell text-start ps-1 w-auto">
				<div class="size08 blue02 weight text-nowrap">$employee_name</div>
				<div class="size06 weight text-nowrap">$employee_type</div>
			</div>
		</div>
	</div>
</div>
EOT;


//從 $team_id 取得團隊名稱
$team_row = getkeyvalue2($site_db."_info","team","team_id = '$team_id'","team_name");
$team_name =$team_row['team_name'];


//取得 上工時間 預設值
$settings_row = getkeyvalue2($site_db."_info","settings","auto_seq = '1'","def_attendance_start");
if (!empty($settings_row['def_attendance_start']))
	$def_attendance_start = $settings_row['def_attendance_start'];
else
	$def_attendance_start = "";



if (!($detect->isMobile() && !$detect->isTablet())) {
	$isMobile = 0;
	

$show_fellow_btn=<<<EOT
<div class="btn-group" role="group">
	<button $disabled type="button" class="btn btn-danger text-nowrap px-4" onclick="openfancybox_edit('/index.php?ch=ch_contract&dispatch_id=$dispatch_id&contract_id=$contract_id&fm=$fm',1024,'96%','');"><i class="bi bi-plus-circle"></i>&nbsp;新增合約工作項目</button>
	<button type="button" class="btn btn-success text-nowrap px-4" onclick="dispatch_member_myDraw();"><i class="bi bi-arrow-repeat"></i>&nbsp;重整</button>
</div>
EOT; 

$show_fellow_btn2=<<<EOT
<div class="btn-group" role="group">
	<button $disabled type="button" class="btn btn-danger px-4" onclick="openfancybox_edit('/index.php?ch=dispatch_material_details_add&dispatch_id=$dispatch_id&contract_id=$contract_id&fm=$fm',800,'96%','');"><i class="bi bi-plus-circle"></i>&nbsp;新增物料名稱/使用機具</button>
	<button type="button" class="btn btn-success text-nowrap px-4" onclick="dispatch_material_details_myDraw();"><i class="bi bi-arrow-repeat"></i>&nbsp;重整</button>
</div>
EOT; 


$style_css=<<<EOT
<style>

.card_full {
    width: 100%;
	height: 100vh;
}

#full {
    width: 100%;
	height: 100%;
}

#info_container {
	width: 100% !Important;
	max-width: 1800px; !Important;
	margin: 0 auto !Important;
}

.field_div1 {width:150px;font-size:18px;color:#000;text-align:right;font-weight:700;padding:15px 10px 0 0;vertical-align: top;display:inline-block;zoom: 1;*display: inline;}
.field_div1a {width:100px;font-size:18px;color:#000;text-align:right;font-weight:700;padding:15px 10px 0 0;vertical-align: top;display:inline-block;zoom: 1;*display: inline;}
.field_div2 {width:100%;max-width:200px;font-size:18px;color:#000;text-align:left;font-weight:700;padding:8px 0 0 0;vertical-align: top;display:inline-block;zoom: 1;*display: inline;}
.field_div2a {width:100%;max-width:280px;font-size:18px;color:#000;text-align:left;font-weight:700;padding:8px 0 0 0;vertical-align: top;display:inline-block;zoom: 1;*display: inline;}
.field_div3 {width:100%;max-width:550px;font-size:18px;color:#000;text-align:left;font-weight:700;padding:8px 0 0 0;vertical-align: top;display:inline-block;zoom: 1;*display: inline;}

.code_class {
	width:150px;
	text-align:right;
	padding:0 10px 0 0;
}

.maxwidth {
    width: 100%;
    max-width: 220px;
}

.maxwidth2 {
    width: 100%;
    max-width: 500px;
}

.maxwidth3 {
    width: 100%;
    max-width: 220px;
}

</style>

EOT;

} else {
	$isMobile = 1;


$show_fellow_btn=<<<EOT
<div class="btn-group" role="group">
	<button $disabled type="button" class="btn btn-danger text-nowrap px-4 text-nowrap" onclick="openfancybox_edit('/index.php?ch=ch_contract&dispatch_id=$dispatch_id&contract_id=$contract_id&fm=$fm',1024,'96%','');"><i class="bi bi-plus-circle"></i>&nbsp;新增合約工作項目</button>
	<button type="button" class="btn btn-success text-nowrap px-4" onclick="dispatch_member_myDraw();"><i class="bi bi-arrow-repeat"></i></button>
</div>
EOT; 

$show_fellow_btn2=<<<EOT
<div class="btn-group" role="group">
	<button $disabled type="button" class="btn btn-danger px-4 text-nowrap" onclick="openfancybox_edit('/index.php?ch=dispatch_material_details_add&dispatch_id=$dispatch_id&contract_id=$contract_id&fm=$fm',800,'96%','');"><i class="bi bi-plus-circle"></i>&nbsp;新增物料名稱/使用機具</button>
	<button type="button" class="btn btn-success text-nowrap px-4" onclick="dispatch_material_details_myDraw();"><i class="bi bi-arrow-repeat"></i>&nbsp;重整</button>
</div>
EOT; 


$style_css=<<<EOT
<style>

.card_full {
    width: 100%;
	height: 100vh;
}

#full {
    width: 100%;
	height: 100%;
}

#info_container {
	width: 100% !Important;
	margin: 0 auto !Important;
}

.field_div1 {width:100%;display: block;font-size:18px;color:#000;text-align:left;font-weight:700;padding:15px 10px 0 0;vertical-align: top;}
.field_div1a {width:auto;font-size:18px;color:#000;text-align:left;font-weight:700;padding:15px 10px 0 0;vertical-align: top;display:inline-block;zoom: 1;*display: inline;}
.field_div2 {width:100%;display: block;font-size:18px;color:#000;text-align:left;font-weight:700;padding:8px 10px 0 0;vertical-align: top;}
.field_div2a {width:auto;font-size:18px;color:#000;text-align:left;font-weight:700;padding:8px 0 0 0;vertical-align: top;display:inline-block;zoom: 1;*display: inline;}
.field_div3 {width:100%;display: block;font-size:18px;color:#000;text-align:left;font-weight:700;padding:8px 10px 0 0;vertical-align: top;}

.code_class {
	width:auto;
	text-align:left;
	padding:0 10px 0 0;
}

.maxwidth {
    width: 100%;
}

.maxwidth2 {
    width: 100%;
}

.maxwidth3 {
    width: 100%;
    max-width: 220px;
}


</style>
EOT;

}


$warning_list = "warning_list".$auto_seq;

$show_ConfirmSending = "";

	if ($ConfirmSending == "Y") {

$show_ConfirmSending=<<<EOT
				<div class="w-100 text-center">
					<button disabled type="button" class="btn btn-secondary btn-lg text-center px-5 m-2"><div class="size12 weight"><i class="bi bi-lock-fill"></i>&nbsp;已確認送出</div><div class="size08 yellow weight">$ConfirmSending_datetime</div></button>
					<!--
					<button $disabled type="button" class="btn btn-warning btn-lg text-center px-5 m-2" onclick="Reduction(this.form);"><div class="size12 weight"><i class="bi bi-unlock-fill"></i>&nbsp;還原確認</div><div class="size08 black weight">(還原後，可進行修改)</div></button>
					-->
				</div>
EOT;

	} else {

$show_ConfirmSending=<<<EOT
				<div class="w-100 text-center">
					<button $disabled type="button" class="btn btn-primary text-center px-4 my-2 mx-1" onclick="Checkall(this.form);"><div class="size12 weight text-nowrap">送出前讓系統再檢查乙次</div></button>
					<button $disabled id="myConfirmSending" disabled type="button" class="btn btn-danger btn-lg text-center px-4 my-2 mx-1" onclick="ConfirmSending(this.form);"><div class="size12 weight text-nowrap">填寫完畢，確認送出</div><div class="size08 yellow weight text-nowrap">(一旦送出即無法修改)</div></button>
				</div>
EOT;

	}


$m_location		= "/website/smarty/templates/".$site_db."/".$templates;

include $m_location."/sub_modal/project/func08/dispatch_ms/dispatch_contract_details.php";

include $m_location."/sub_modal/project/func08/dispatch_ms/dispatch_material_details.php";


$now = date('Y-m-d  H:i');


$show_notify_times = "";
if ($notify_times > 0) {
	$show_notify_times = "<div class=\"text-center\">已發送通知 <span class=\"red weight\">".$notify_times."</span> 次，最後通知： ".$last_webpush."</div>";
}


$show_center=<<<EOT
<script src="https://cdnjs.cloudflare.com/ajax/libs/socket.io/2.1.1/socket.io.js"></script>

<script src="/os/Autogrow-Textarea/jquery.autogrowtextarea.min.js"></script>

$style_css

<style>

    .tooltip-box {
        position: absolute;
        top: 50px; /* 調整為按鈕正下方 */
        left: 30%;
        transform: translateX(-30%);
        padding: 10px;
        background-color: #333;
        color: white;
        border-radius: 5px;
        display: none;
		/*
        white-space: nowrap;
		*/
        z-index: 1000;
    }

    .tooltip-box::after {
        content: '';
        position: absolute;
        top: -8px;
        left: 30%;
        transform: translateX(-30%);
        border-width: 8px;
        border-style: solid;
        border-color: transparent transparent #333 transparent;
    }
</style>

<div class="card card_full">
	<div class="card-header text-bg-info">
		<div class="size14 weight float-start" style="margin-top: 5px;">
			$mess_title
		</div>
		<div class="float-end" style="margin-top: -5px;">
			$show_savebtn
		</div>
	</div>
	<div id="full" class="card-body" data-overlayscrollbars-initialize>
		<div id="info_container">
			<form method="post" id="modifyForm" name="modifyForm" enctype="multipart/form-data" action="javascript:void(null);">
			<div class="w-100 mb-5">
				<div class="field_container3">
					<div class="container-fluid">
						<div class="row">
							<div class="col-lg-3 col-sm-12 col-md-12">
								<div class="field_div1a">日期:</div> 
								<div class="field_div2a">
									<div class="inline weight blue02 pt-2 me-2">$dispatch_date</div>
									<div class="inline red pt-2 text-nowrap">(#{$dispatch_id})</div>
								</div> 
							</div> 
							<!--
							<div class="col-lg-3 col-sm-12 col-md-12">
								<div class="field_div1a">公司:</div> 
								<div class="field_div2a pt-3">
									$show_company_name
								</div> 
							</div> 
							-->
							<div class="col-lg-9 col-sm-12 col-md-12">
								<div class="field_div1a">合約:</div> 
								<div class="field_div2a pt-3">
									$show_contract
								</div> 
								$member_logo
							</div> 
						</div>
					</div>
					<hr class="style_b">
					<div class="container-fluid">
						<div class="row">
							<div class="col-lg-6 col-sm-12 col-md-12 p-2">
								<div class="w-100 text-center">
									<div class="inline size14 weight me-3">任務內容</div>
									<div class="inline me-1">
										<div class="btn-group" role="group">
											<!--
											<button $disabled class="btn btn-light text-nowrap" type="button" onclick="SaveValue(this.form);copyToClipboard();"><i class="bi bi-copy"></i>&nbsp;複製任務內容</button>
											-->
											<button $disabled class="btn btn-light text-nowrap" type="button" onclick="SaveValue(this.form);PushNotice(this.form);"><i class="bi bi-bell"></i>&nbsp;通知</button>
											<button $disabled class="btn btn-light text-nowrap" type="button" onclick="parent.openfancybox_edit('/index.php?tb=dispatch&auto_seq=$auto_seq&project_id=$project_id&auth_id=$auth_id&fm=pjattach','96%','96%','parent.myDraw();');"><i class="bi bi-file-arrow-up"></i>&nbsp;上傳檔案&nbsp;$show_files_total</button>
										</div>
									</div>
									<div class="inline size14 weight ">
										<input type="checkbox" class="inputtext" name="posted" id="posted" value="Y" $m_posted />
										<label for="posted" class="red">公佈</label>
									</div>
								</div> 
								<div class="w-100 text-center mt-1">
									<textarea $disabled class="inputtext w-100 p-3" id="content" name="content" cols="80" rows="5" style="max-width: 840px;" onchange="setEdit();">$content</textarea>
									<!--
									<script>
										function copyToClipboard() {
											// 取得 textarea 元素
											var textarea = document.getElementById("content");

											// 選取 textarea 的內容
											textarea.select();
											textarea.setSelectionRange(0, 99999); // 針對行動裝置

											// 複製選取的文字到剪貼簿
											document.execCommand("copy");

											textarea.blur();

											// 提示訊息
											//alert("文字已複製到剪貼簿: " + textarea.value);
											jAlert('提示', '已複製', 'green', '', 1000);
										}
									</script>
									-->
								</div> 
								$show_notify_times
							</div>
							<div class="col-lg-3 col-sm-12 col-md-12 p-2">
								<div class="w-100 mt-3">
									<div class="size14 weight">工安注意事項</div>
									<div class="w-100">
										<textarea class="inputtext w-100 p-3" id="task_safety_tips" name="task_safety_tips" cols="80" rows="2" style="max-width: 420px;">$task_safety_tips</textarea>
									</div> 
								</div>
							</div>
							<div class="col-lg-3 col-sm-12 col-md-12 p-2">
								<div class="w-100 mt-3">
									<div class="size14 weight">待辦事項</div>
									<div class="w-100">
										<textarea class="inputtext w-100 p-3" id="todolist" name="todolist" cols="80" rows="2" style="max-width: 420px;">$todolist</textarea>
									</div> 
								</div>
							</div>
						</div>
					</div>					
					<hr class="style_b">
					<div class="container-fluid">
						<div class="row">
							<div class="col-lg-12 col-sm-12 col-md-12">
								<div class="inline size14 weight mx-5">合約工項/派工:</div> 
								<div class="inline">
									$show_fellow_btn
								</div> 
							</div> 
							<!--
							<div class="col-lg-5 col-sm-12 col-md-12 pt-1">
								$show_Knock_off_btn
								<div class="tooltip-box" id="tooltipContent1">
									<div class="size14" style="width:100%;max-width: 360px;">
										按下「收工作業」鈕即進行統一收工的作業程序，但有些狀況系統尚無法完全正確判別，仍請您再自行審視修正，謝謝。
									</div>
								</div>
								<div class="tooltip-box" id="tooltipContent2">
									<div class="size14" style="width:100%;max-width: 360px;">
										按下「人數及工時計算」鈕即進行人數及工時的自動計算作業，但有些狀況系統尚無法完全正確算出，仍請您再自行審視修正，謝謝。
									</div>
								</div>
							</div> 
							-->
						</div>
					</div>
					$show_dispatch_contract_details
					<div class="container-fluid">
						<div class="row">
							<div class="col-lg-12 col-sm-12 col-md-12">
								<div class="inline size14 weight mx-5">物料名稱/使用機具:</div> 
								<div class="inline">
									$show_fellow_btn2
								</div> 
							</div> 
						</div>
					</div>
					$show_dispatch_material_details
					<div class="container-fluid">
						<div class="row">
							<div class="col-lg-12 col-sm-12 col-md-12">
								<div id="$warning_list" class="w-100 size14 red weight p-1 text-center"></div>
								$show_ConfirmSending
							</div>
						</div>
					</div>
				</div>
				<div>
					<input type="hidden" name="fm" value="$fm" />
					<input type="hidden" name="site_db" value="$site_db" />
					<input type="hidden" name="web_id" value="$web_id" />
					<input type="hidden" name="project_id" value="$project_id" />
					<input type="hidden" name="auth_id" value="$auth_id" />
					<input type="hidden" name="caption" value="$caption" />
					<input type="hidden" name="employee_name" value="$employee_name" />
					<input type="hidden" name="auto_seq" value="$auto_seq" />
					<input type="hidden" name="dispatch_id" value="$dispatch_id" />
					<input type="hidden" name="team_id" value="$team_id" />
					<input type="hidden" name="memberID" value="$memberID" />
					<input type="hidden" name="employee_id" value="$employee_id" />
				</div>
			</div>
			</form>
		</div>
	</div>
</div>
<script>

function CheckValue(thisform) {
	xajax_processform(xajax.getFormValues('modifyForm'));
	thisform.submit();
}

function SaveValue(thisform) {
	xajax_SaveValue(xajax.getFormValues('modifyForm'));
	//thisform.submit();
}

function setEdit() {
	$('#close', window.document).addClass("display_none");
	$('#cancel', window.document).removeClass("display_none");
	$('#myConfirmSending').prop('disabled', true);
}

function setCancel() {
	$('#close', window.document).removeClass("display_none");
	$('#cancel', window.document).addClass("display_none");
	document.forms[0].reset();
}

function setSave() {
	$('#close', window.document).removeClass("display_none");
	$('#cancel', window.document).addClass("display_none");
}

function ch_team_member(thisform) {

	var dispatch_id = thisform.dispatch_id.value;
	var team_id = thisform.team_id.value;
	//alert(team_id);

	openfancybox_edit('/index.php?ch=ch_team_member&dispatch_id='+dispatch_id+'&team_id='+team_id+'&fm=$fm',800,'96%','');
}


$(document).ready(function() {
	$("#content").autoGrow({
		extraLine: true // Adds an extra line at the end of the textarea. Try both and see what works best for you.
	});
	$("#task_safety_tips").autoGrow({
		extraLine: true // Adds an extra line at the end of the textarea. Try both and see what works best for you.
	});
	$("#todolist").autoGrow({
		extraLine: true // Adds an extra line at the end of the textarea. Try both and see what works best for you.
	});
});


var PushNotice = function(thisform) {	

	let  dispatch_id = thisform.dispatch_id.value;
	let  caption = thisform.caption.value;
	let  employee_name = thisform.employee_name.value;
	let  io_content = thisform.content.value;

	Swal.fire({
	title: "您確定要發佈「任務內容」通知嗎?",
	text: "",
	icon: "question",
	showCancelButton: true,
	confirmButtonColor: "#3085d6",
	cancelButtonColor: "#d33",
	cancelButtonText: "取消",
	confirmButtonText: "確定"
	}).then((result) => {
		if (result.isConfirmed) {
			SendPushNotices('dispatch',dispatch_id,io_content);
		}
	});
	
};

var SendPushNotices = function(tb,dispatch_id,io_content){
	var site_db = '$site_db';
	var web_id = '$web_id';
	var fm = '$fm';
	var templates = '$templates';
	var memberID = '$memberID';
	var project_id = '$project_id';
	var auth_id = '$auth_id';
	var caption = '$caption';
	var member_name = '$employee_name';
	var dispatch_date = '$dispatch_date';
	var now = '$now';
	//存入訊息
	$.post("/smarty/templates/"+site_db+"/"+templates+"/sub_modal/project/func08/dispatch_ms/ajax_PushNotice.php",{
			"site_db": site_db,
			"web_id": web_id,
			"project_id": project_id,
			"auth_id": auth_id,
			"from_id": memberID,
			"tb": tb,
			"dispatch_id": dispatch_id,
			"PushContent": io_content
			},
		function(data){

			var dispatch_desc = "日期："+dispatch_date+" (#"+dispatch_id+")";
			//var PushContent = member_name+" 於 "+now+" 發出了通知訊息<br>"+caption+"<br>"+dispatch_desc+"<br>"+io_content;

			var url = "/index.php?ch=view&pjt="+caption+"&dispatch_id="+dispatch_id+"&project_id="+project_id+"&auth_id="+auth_id+"&fm="+fm+"#myScrollspy";

			var mynotices_message = caption+"<div class=\"mytable\"><div class=\"myrow\"><div class=\"mycell w-auto px-1\"><div><div class=\"size12 weight\">"+member_name+" 於 <span class=\"red\">"+now+"</span> 發出了通知訊息</div></div><div style=\"padding: 0 3px 3px 0;\"><div class=\"size12 blue weight\">出工任務內容</div><div class=\"size12 weight\">"+dispatch_desc+"</div><div class=\"block-with-text\" style=\"max-height: 7.2em;\">"+io_content+"</div></div></div></div></div>";


			io.connect('$FOREVER').emit('sendnotice', '$web_id', data, mynotices_message);

			jAlert('提示', '已發佈通知!', 'green', '', 1000);


		},
		"json"
	);
}

var Checkall = function(thisform) {	
	xajax_Checkall(xajax.getFormValues('modifyForm'));
};


var ConfirmSending = function(thisform) {	

	//var dispatch_id = thisform.dispatch_id.value;
	//var content = thisform.content.value;

	Swal.fire({
	title: "請確認已完成資料檢查驗證，您確定要「確認送出」嗎?",
	text: "",
	icon: "question",
	showCancelButton: true,
	confirmButtonColor: "#3085d6",
	cancelButtonColor: "#d33",
	cancelButtonText: "取消",
	confirmButtonText: "確定"
	}).then((result) => {
		if (result.isConfirmed) {
			xajax_ConfirmSending(xajax.getFormValues('modifyForm'));
		}
	});

};

var Reduction = function(thisform) {

	Swal.fire({
	title: "您確定要還原嗎?",
	text: "",
	icon: "question",
	showCancelButton: true,
	confirmButtonColor: "#3085d6",
	cancelButtonColor: "#d33",
	cancelButtonText: "取消",
	confirmButtonText: "確定"
	}).then((result) => {
		if (result.isConfirmed) {
			xajax_Reduction(xajax.getFormValues('modifyForm'));
		}
	});

}

</script>

<script>
	// 取得按鈕和提示框元素
	const tooltipButton1 = document.getElementById('tooltipButton1');
	const tooltipContent1 = document.getElementById('tooltipContent1');

	let timeoutId; // 記錄計時器的ID

	// 設定按鈕點擊事件，控制提示框的顯示與隱藏
	tooltipButton1.addEventListener('click', function() {
		if (tooltipContent1.style.display === 'none' || tooltipContent1.style.display === '') {
			tooltipContent1.style.display = 'block';

			// 清除之前的計時器，避免重複計時
			clearTimeout(timeoutId);

			// 設定7秒後自動隱藏
			timeoutId = setTimeout(function() {
			tooltipContent1.style.display = 'none';
			}, 7000); // 7000毫秒 = 7秒
		} else {
			tooltipContent1.style.display = 'none';
			clearTimeout(timeoutId); // 如果手動關閉，清除計時器
		}
	});

	// 取得按鈕和提示框元素
	const tooltipButton2 = document.getElementById('tooltipButton2');
	const tooltipContent2 = document.getElementById('tooltipContent2');

	// 設定按鈕點擊事件，控制提示框的顯示與隱藏
	tooltipButton2.addEventListener('click', function() {
		if (tooltipContent2.style.display === 'none' || tooltipContent2.style.display === '') {
			tooltipContent2.style.display = 'block';

			// 清除之前的計時器，避免重複計時
			clearTimeout(timeoutId);

			// 設定7秒後自動隱藏
			timeoutId = setTimeout(function() {
			tooltipContent2.style.display = 'none';
			}, 7000); // 7000毫秒 = 7秒
		} else {
			tooltipContent2.style.display = 'none';
			clearTimeout(timeoutId); // 如果手動關閉，清除計時器
		}
	});


var updispatch = function(){

	var site_db = '$site_db';
	var templates = '$templates';
	var dispatch_id = '$dispatch_id';

	var url = '/smarty/templates/'+site_db+'/'+templates+'/sub_modal/project/func08/dispatch_ms/ajax_update_dispatch.php'; 

	$.ajax({
		url: url, 
		type: 'GET',
		data: { dispatch_id: dispatch_id },
		dataType: 'text', 
		success: function(data) {
		},
		error: function() {
		}
	});

}

</script>

EOT;

?>