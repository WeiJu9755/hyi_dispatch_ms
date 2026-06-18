<?php

session_start();
$memberID = $_SESSION['memberID'];
$powerkey = $_SESSION['powerkey'];


//計算出勤時間
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
    //if ($start < $workStartTime) $start = $workStartTime;
    //if ($end > $workEndTime) $end = $workEndTime;

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

@include_once("/website/class/".$site_db."_info_class.php");

/* 使用xajax */
@include_once '/website/xajax/xajax_core/xajax.inc.php';
$xajax = new xajax();

$xajax->registerFunction("processform");
$xajax->registerFunction("check_employee_dispatch");

function showEmployeeDispatchNotice($employee_id,$dispatch_id,$site_db){

	$employee_id = trim($employee_id);
	$dispatch_id = trim($dispatch_id);
	$site_db = trim($site_db);

	if ($employee_id == "" || $dispatch_id == "") {
		return "";
	}

	$dispatch_row = getkeyvalue2($site_db."_info","dispatch","dispatch_id = '$dispatch_id'","dispatch_date");
	$dispatch_date = $dispatch_row['dispatch_date'];

	if ($dispatch_date == "") {
		return "<div class=\"alert alert-warning py-2 px-3 mt-2 mb-0 size12\">無法取得派工日期，暫時不能檢查員工當日派工狀態。</div>";
	}

	$mDB = "";
	$mDB = new MywebDB();

	$Qry = "SELECT a.dispatch_id,a.contract_id,a.seq,a.attendance_start,a.attendance_end,a.attendance_hours,a.attendance_remark,a.is_overtime,b.dispatch_date,c.work_project
		FROM dispatch_attendance_sub a
		LEFT JOIN dispatch b ON b.dispatch_id = a.dispatch_id
		LEFT JOIN contract_details c ON c.contract_id = a.contract_id AND c.seq = a.seq
		WHERE b.dispatch_date = '$dispatch_date' AND a.employee_id = '$employee_id'
		ORDER BY a.dispatch_id,a.contract_id,a.seq,a.attendance_start";
	$mDB->query($Qry);

	$total = $mDB->rowCount();
	if ($total <= 0) {
		$mDB->remove();
		return "<div class=\"alert alert-success py-2 px-3 mt-2 mb-0 size12\">提醒：此員工於 $dispatch_date 尚無已派工紀錄。</div>";
	}

	$total_hours = 0;
	$notice_list = "";
	while ($row=$mDB->fetchRow(2)) {
		$show_dispatch_id = htmlspecialchars($row['dispatch_id'], ENT_QUOTES, 'UTF-8');
		$show_contract_id = htmlspecialchars($row['contract_id'], ENT_QUOTES, 'UTF-8');
		$show_seq = htmlspecialchars($row['seq'], ENT_QUOTES, 'UTF-8');
		$show_work_project = htmlspecialchars($row['work_project'], ENT_QUOTES, 'UTF-8');
		$show_attendance_remark = htmlspecialchars($row['attendance_remark'], ENT_QUOTES, 'UTF-8');
		$attendance_hours = (float)$row['attendance_hours'];
		$total_hours += $attendance_hours;

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

		$overtime_text = ($row['is_overtime'] == "Y") ? "<span class=\"badge text-bg-warning ms-1\">加班</span>" : "";
		$remark_text = ($show_attendance_remark != "") ? "<div class=\"small text-muted\">備註：$show_attendance_remark</div>" : "";

		$notice_list .= "<li class=\"mb-1\">#$show_dispatch_id / $show_contract_id-$show_seq $show_work_project<br><span class=\"text-nowrap\">$attendance_start ~ $attendance_end ， $attendance_hours 小時</span>$overtime_text$remark_text</li>";
	}
	$mDB->remove();

	return "<div class=\"alert alert-warning py-2 px-3 mt-2 mb-0 size12\"><div class=\"weight mb-1\">提醒：此員工於 $dispatch_date 已有派工，共 $total 小筆，合計 $total_hours 小時。</div><ul class=\"mb-0 ps-3\">$notice_list</ul></div>";
}

function check_employee_dispatch($employee_id,$dispatch_id,$site_db){

	$objResponse = new xajaxResponse();
	$notice_html = showEmployeeDispatchNotice($employee_id,$dispatch_id,$site_db);
	$objResponse->assign("employee_dispatch_notice","innerHTML",$notice_html);

	return $objResponse;
}

function processform($aFormValues){

	$objResponse = new xajaxResponse();
	
	
	$fm					= trim($aFormValues['fm']);
	$site_db			= trim($aFormValues['site_db']);
	$templates			= trim($aFormValues['templates']);
	$web_id				= trim($aFormValues['web_id']);
	
	$dispatch_id		= trim($aFormValues['dispatch_id']);
	$contract_id		= trim($aFormValues['contract_id']);
	$seq				= trim($aFormValues['seq']);


	if (trim($aFormValues['employee_id']) == "")	{
		$objResponse->script("jAlert('警示', '請選擇員工', 'red', '', 2000);");
		return $objResponse;
		exit;
	}

	if (trim($aFormValues['attendance_start']) == "")	{
		$objResponse->script("jAlert('警示', '請輸入開始時間', 'red', '', 2000);");
		return $objResponse;
		exit;
	}

	if (trim($aFormValues['attendance_end']) == "")	{
		$objResponse->script("jAlert('警示', '請輸入迄止時間', 'red', '', 2000);");
		return $objResponse;
		exit;
	}


	$employee_id		= trim($aFormValues['employee_id']);
	$attendance_start	= trim($aFormValues['attendance_start']);
	$attendance_end		= trim($aFormValues['attendance_end']);
	$attendance_remark	= trim($aFormValues['attendance_remark']);
	$is_overtime		= trim($aFormValues['is_overtime']);

	$attendance_hours = calculateLeaveHours($attendance_start,$attendance_end);


	//存入實體資料庫中
	$mDB = "";
	$mDB = new MywebDB();
	
	$Qry="INSERT INTO dispatch_attendance_sub (dispatch_id,contract_id,seq,employee_id,attendance_start,attendance_end,attendance_hours,attendance_remark,is_overtime) VALUES 
		('$dispatch_id','$contract_id','$seq','$employee_id','$attendance_start','$attendance_end','$attendance_hours','$attendance_remark','$is_overtime')";
	$mDB->query($Qry);

	$mDB->remove();

	$objResponse->script("parent.dispatch_attendance_sub_myDraw();");
	$objResponse->script("parent.$.fancybox.close();");
		
	return $objResponse;	
}

$xajax->processRequest();

$fm = $_GET['fm'];
$dispatch_id = $_GET['dispatch_id'];
$contract_id = $_GET['contract_id'];
$seq = $_GET['seq'];

$mess_title = $title;


//取得 上工時間及收工時間 預設值
$settings_row = getkeyvalue2($site_db."_info","settings","auto_seq = '1'","def_attendance_start,def_attendance_end");

if (!empty($settings_row['def_attendance_start']))
	$def_attendance_start = date("H:i",strtotime($settings_row['def_attendance_start']));
else
	$def_attendance_start = "";

	if (!empty($settings_row['def_attendance_end']))
	$def_attendance_end = date("H:i",strtotime($settings_row['def_attendance_end']));
else
	$def_attendance_end = "";


if (!($detect->isMobile() && !$detect->isTablet())) {
	$isMobile = 0;
	
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

.field_div1 {width:200px;display: none;font-size:18px;color:#000;text-align:right;font-weight:700;padding:15px 10px 0 0;vertical-align: top;display:inline-block;zoom: 1;*display: inline;}
.field_div2 {width:100%;max-width:450px;display: none;font-size:18px;color:#000;text-align:left;font-weight:700;padding:8px 0 0 0;vertical-align: top;display:inline-block;zoom: 1;*display: inline;}

.code_class {
	width:150px;
	text-align:right;
	padding:0 10px 0 0;
}

.maxwidth {
    width: 100%;
    max-width: 300px;
}

</style>

EOT;

} else {
	$isMobile = 1;

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
.field_div2 {width:100%;display: block;font-size:18px;color:#000;text-align:left;font-weight:700;padding:8px 10px 0 0;vertical-align: top;}

.code_class {
	width:auto;
	text-align:left;
	padding:0 10px 0 0;
}

.maxwidth {
    width: 100%;
}

</style>
EOT;

}


$show_center=<<<EOT
<link rel="stylesheet" type="text/css" href="/os/clockpicker-gh-pages/dist/jquery-clockpicker.css">
<script type="text/javascript" src="/os/clockpicker-gh-pages/dist/jquery-clockpicker.js"></script>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
$style_css
<div class="card card_full">
	<div class="card-header text-bg-info">
		<div class="size14 weight float-start me-3 mt-2">
			$mess_title
		</div>
	</div>
	<div id="full" class="card-body data-overlayscrollbars-initialize">
		<div id="info_container">
			<form method="post" id="addForm" name="addForm" enctype="multipart/form-data" action="javascript:void(null);">
				<div class="field_container3">
					<div>
						<div class="field_div1">派工人員:</div> 
						<div class="field_div2">
							<div class="input-group text-nowrap" style="width:100%;max-width:450px;">
								<input readonly type="text" class="form-control w-25" id="employee_id" name="employee_id" aria-describedby="employee_id_addon" value="$employee_id"/>
								<input readonly type="text" class="form-control w-50" id="employee_name" name="employee_name"  value="$employee_name"/>
								<button class="btn btn-outline-secondary w-25" type="button" id="employee_id_addon" onclick="openfancybox_edit('/index.php?ch=ch_employee&fm=$fm',700,'90%','');">選擇員工</button>
							</div>
							<div id="employee_dispatch_notice"></div>
						</div>
					</div>
					<div>
						<div class="field_div1">開始時間:</div> 
						<div class="field_div2">
							<div class="input-group w-100 clockpicker_start" style="max-width:220px;">
								<input type="text" class="form-control" id="attendance_start" name="attendance_start" placeholder="請輸入開始時間" value="$def_attendance_start">
								<button class="btn btn-outline-secondary input-group-append input-group-addon" type="button"><i class="bi bi-clock"></i></button>
								<button class="btn btn-outline-secondary input-group-append input-group-addon" type="button" onclick="clear_attendance_start();"><i class="bi bi-x-lg"></i></button>
							</div>
							<script type="text/javascript">
								var clockInput = $('.clockpicker_start').clockpicker({
									autoclose: true
								});
							</script>
						</div> 
					</div>
					<div>
						<div class="field_div1">迄止時間:</div> 
						<div class="field_div2">
							<div class="input-group w-100 clockpicker_end" style="max-width:220px;">
								<input type="text" class="form-control" id="attendance_end" name="attendance_end" placeholder="請輸入迄止時間" value="$def_attendance_end">
								<button class="btn btn-outline-secondary input-group-append input-group-addon" type="button"><i class="bi bi-clock"></i></button>
								<button class="btn btn-outline-secondary input-group-append input-group-addon" type="button" onclick="clear_attendance_end();"><i class="bi bi-x-lg"></i></button>
							</div>
							<script type="text/javascript">
								var clockInput = $('.clockpicker_end').clockpicker({
									autoclose: true
								});
							</script>
						</div> 
					</div>
					<div>
						<div class="field_div1">備註:</div> 
						<div class="field_div2">
							<input type="text" class="inputtext w-100" id="attendance_remark" name="attendance_remark" size="50" maxlength="160" value="$attendance_remark" style="max-width:340px;"/>
						</div> 
					</div>
					<div>
						<div class="field_div1">是否加班:</div> 
						<div class="field_div2 pt-3">
							<input type="checkbox" class="inputtext" name="is_overtime" id="is_overtime" value="Y" />
							<label for="is_overtime" class="red">加班</label>
						</div>
					</div>
				</div>
				<div class="form_btn_div mt-5">
					<input type="hidden" name="fm" value="$fm" />
					<input type="hidden" name="site_db" value="$site_db" />
					<input type="hidden" name="templates" value="$templates" />
					<input type="hidden" name="web_id" value="$web_id" />
					<input type="hidden" name="dispatch_id" value="$dispatch_id" />
					<input type="hidden" name="contract_id" value="$contract_id" />
					<input type="hidden" name="seq" value="$seq" />
					<input type="hidden" name="memberID" value="$memberID" />
					<button class="btn btn-primary" type="button" onclick="CheckValue(this.form);" style="padding: 10px;margin-right: 10px;"><i class="bi bi-check-lg green"></i>&nbsp;確定新增</button>
					<button class="btn btn-danger" type="button" onclick="parent.$.fancybox.close();" style="padding: 10px;"><i class="bi bi-power"></i>&nbsp關閉</button>
				</div>
			</form>
		</div>
	</div>
</div>
<script>

function CheckValue(thisform) {
	xajax_processform(xajax.getFormValues('addForm'));
	thisform.submit();
}

function check_employee_dispatch_notice() {
	var employee_id = $("#employee_id").val();
	if (employee_id != "") {
		xajax_check_employee_dispatch(employee_id, "$dispatch_id", "$site_db");
	} else {
		$("#employee_dispatch_notice").html("");
	}
}

var myDraw = function(){
	var oTable;
	oTable = parent.$('#dispatch_attendance_sub_table').dataTable();
	oTable.fnDraw(false);
}
	
function clear_attendance_start() {
	$("#attendance_start").val("");
}

function clear_attendance_end() {
	$("#attendance_end").val("");
}

</script>
EOT;


?>
