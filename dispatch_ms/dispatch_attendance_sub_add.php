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
						</div>
					</div>
					<div>
						<div class="field_div1">開始時間:</div> 
						<div class="field_div2">
							<div class="input-group w-100 clockpicker_start" style="max-width:220px;">
								<input readonly type="text" class="form-control" id="attendance_start" name="attendance_start" placeholder="請輸入開始時間" value="$def_attendance_start">
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
								<input readonly type="text" class="form-control" id="attendance_end" name="attendance_end" placeholder="請輸入迄止時間" value="$def_attendance_end">
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