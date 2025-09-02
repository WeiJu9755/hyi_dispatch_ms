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

function processform($aFormValues){

	$objResponse = new xajaxResponse();
	
	$fm					= trim($aFormValues['fm']);
	$site_db			= trim($aFormValues['site_db']);
	$templates			= trim($aFormValues['templates']);
	$web_id				= trim($aFormValues['web_id']);
	$auto_seq			= trim($aFormValues['auto_seq']);
	$dispatch_id		= trim($aFormValues['dispatch_id']);
	$employee_id		= trim($aFormValues['employee_id']);
	$attendance_start	= trim($aFormValues['attendance_start']);
	$attendance_end		= trim($aFormValues['attendance_end']);
	$attendance_remark	= trim($aFormValues['attendance_remark']);
	$is_overtime		= trim($aFormValues['is_overtime']);


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


	$attendance_hours = calculateLeaveHours($attendance_start,$attendance_end);
	
		
	//存入實體資料庫中
	$mDB = "";
	$mDB = new MywebDB();

	$Qry="UPDATE dispatch_attendance_sub set
			 attendance_start	= '$attendance_start'
			,attendance_end		= '$attendance_end'
			,attendance_hours	= '$attendance_hours'
			,attendance_remark	= '$attendance_remark'
			,is_overtime		= '$is_overtime'
			where auto_seq = '$auto_seq'";

	$mDB->query($Qry);

	$mDB->remove();

	$objResponse->script("parent.dispatch_attendance_sub_myDraw();");
	$objResponse->script("parent.$.fancybox.close();");
		
	
	return $objResponse;	
}

$xajax->processRequest();

$fm = $_GET['fm'];
$auto_seq = $_GET['auto_seq'];

$mess_title = $title;



$mDB = "";
$mDB = new MywebDB();

$Qry="SELECT a.*,b.employee_name FROM dispatch_attendance_sub a
LEFT JOIN employee b ON b.employee_id = a.employee_id
WHERE a.auto_seq = '$auto_seq'";

$mDB->query($Qry);
$total = $mDB->rowCount();
if ($total > 0) {
    //已找到符合資料
	$row=$mDB->fetchRow(2);
	$employee_id = $row['employee_id'];
	$employee_name = $row['employee_name'];
	$attendance_remark = $row['attendance_remark'];
	$is_overtime = $row['is_overtime'];

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

	if ($is_overtime=="Y")
	  $m_is_overtime = "checked=\"checked\"";

}

$mDB->remove();


if (!($detect->isMobile() && !$detect->isTablet())) {
	$isMobile = 0;

$style_css=<<<EOT
<style>

.card_full {
    width: 100vw;
	height: 100vh;
}

#full {
    width: 100vw;
	height: 100vh;
}

#info_container {
	width: 600px !Important;
	margin: 0 auto !Important;
}

.field_div1 {width:150px;display: none;font-size:18px;color:#000;text-align:right;font-weight:700;padding:15px 10px 0 0;vertical-align: top;display:inline-block;zoom: 1;*display: inline;}
.field_div2 {width:100%;max-width:430px;display: none;font-size:18px;color:#000;text-align:left;font-weight:700;padding:8px 0 0 0;vertical-align: top;display:inline-block;zoom: 1;*display: inline;}

.maxwidth {
    width: 100%;
    max-width: 250px;
}
</style>
EOT;

} else {
	$isMobile = 1;
$style_css=<<<EOT
<style>

.card_full {
    width: 100vw;
	height: 100vh;
}

#full {
    width: 100vw;
	height: 100vh;
}

#info_container {
	width: 100% !Important;
	margin: 0 auto !Important;
}

.field_div1 {width:100%;display: block;font-size:18px;color:#000;text-align:left;font-weight:700;padding:15px 10px 0 0;vertical-align: top;}
.field_div2 {width:100%;display: block;font-size:18px;color:#000;text-align:left;font-weight:700;padding:8px 10px 0 0;vertical-align: top;}

.maxwidth {
    width: 100%;
}
</style>
EOT;

}

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
			<form method="post" id="modifyForm" name="modifyForm" enctype="multipart/form-data" action="javascript:void(null);">
				<div class="field_container3">
					<div>
						<div class="field_div1">派工人員:</div> 
						<div class="field_div2">
							<div class="inline blue02 text-nowrap mt-2 me-2">$employee_name</div>
							<div class="inline size08 gray text-nowrap">$employee_id</div>
						</div> 
					</div>
					<div>
						<div class="field_div1">開始時間:</div> 
						<div class="field_div2">
							<div class="input-group w-100 clockpicker_start" style="max-width:220px;">
								<input readonly type="text" class="form-control" id="attendance_start" name="attendance_start" placeholder="請輸入開始時間" value="$attendance_start">
								<button class="btn btn-outline-secondary input-group-append input-group-addon" type="button"><i class="bi bi-clock"></i></button>
								<button class="btn btn-outline-secondary input-group-append input-group-addon" type="button" onclick="clear_attendance_start();"><i class="bi bi-x-lg"></i></button>
							</div>
							<script type="text/javascript">
								var clockInput = $('.clockpicker_start').clockpicker({
									autoclose: false
								});
							</script>
						</div> 
					</div>
					<div>
						<div class="field_div1">迄止時間:</div> 
						<div class="field_div2">
							<div class="input-group w-100 clockpicker_end" style="max-width:220px;">
								<input readonly type="text" class="form-control" id="attendance_end" name="attendance_end" placeholder="請輸入迄止時間" value="$attendance_end">
								<button class="btn btn-outline-secondary input-group-append input-group-addon" type="button"><i class="bi bi-clock"></i></button>
								<button class="btn btn-outline-secondary input-group-append input-group-addon" type="button" onclick="clear_attendance_end();"><i class="bi bi-x-lg"></i></button>
							</div>
							<script type="text/javascript">
								var clockInput = $('.clockpicker_end').clockpicker({
									autoclose: false
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
							<input type="checkbox" class="inputtext" name="is_overtime" id="is_overtime" value="Y" $m_is_overtime />
							<label for="is_overtime" class="red">加班</label>
						</div>
					</div>
				</div>
				<div class="form_btn_div mt-5">
					<input type="hidden" name="fm" value="$fm" />
					<input type="hidden" name="site_db" value="$site_db" />
					<input type="hidden" name="templates" value="$templates" />
					<input type="hidden" name="web_id" value="$web_id" />
					<input type="hidden" name="auto_seq" value="$auto_seq" />
					<input type="hidden" name="memberID" value="$memberID" />
					<button class="btn btn-primary" type="button" onclick="CheckValue(this.form);" style="padding: 10px;margin-right: 10px;"><i class="bi bi-check-lg green"></i>&nbsp;存檔</button>
					<button class="btn btn-danger" type="button" onclick="parent.$.fancybox.close();" style="padding: 10px;"><i class="bi bi-power"></i>&nbsp關閉</button>
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