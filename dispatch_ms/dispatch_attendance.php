<?php

session_start();

$memberID = $_SESSION['memberID'];
$powerkey = $_SESSION['powerkey'];


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
	$auto_seq			= trim($aFormValues['auto_seq']);
	/*	
	if (trim($aFormValues['salary']) <= 0)	{
		$objResponse->script("jAlert('警示', '請輸入調整薪資', 'red', '', 2000);");
		return $objResponse;
		exit;
	}
	if (trim($aFormValues['adjustment_date']) == "")	{
		$objResponse->script("jAlert('警示', '請輸入調整生效日期', 'red', '', 2000);");
		return $objResponse;
		exit;
	}
	
	SaveValue($aFormValues);
	
	$objResponse->script("setSave();");
	$objResponse->script("parent.myDraw();");

	$objResponse->script("art.dialog.tips('已存檔!',1);");
	$objResponse->script("parent.$.fancybox.close();");
	*/
	
	return $objResponse;
}


$xajax->registerFunction("SaveValue");
function SaveValue($aFormValues){

	$objResponse = new xajaxResponse();
	
		//進行存檔動作
		$site_db				= trim($aFormValues['site_db']);
		$web_id					= trim($aFormValues['web_id']);
		$auto_seq				= trim($aFormValues['auto_seq']);
		$salary					= trim($aFormValues['salary']);
		$adjustment_date		= trim($aFormValues['adjustment_date']);
		$confirm				= trim($aFormValues['confirm']);

		//存入info實體資料庫中
		$mDB = "";
		$mDB = new MywebDB();

		$Qry="UPDATE salary_expenses set
				 salary				= '$salary'
				,adjustment_date	= '$adjustment_date'
				,confirm			= '$confirm'
				,last_modify		= now()
				where auto_seq = '$auto_seq'";
				
		$mDB->query($Qry);
        $mDB->remove();

		
	return $objResponse;
}

$xajax->registerFunction("dispatch_attendance_sub_DeleteRow");
function dispatch_attendance_sub_DeleteRow($auto_seq){

	$objResponse = new xajaxResponse();
	
	$mDB = "";
	$mDB = new MywebDB();

	//刪除主資料
	$Qry="delete from dispatch_attendance_sub where auto_seq = '$auto_seq'";
	$mDB->query($Qry);
	
	$mDB->remove();
	
    $objResponse->script("dispatch_attendance_sub_myDraw();");
    $objResponse->script("art.dialog.tips('已刪除!',2)");

	return $objResponse;
	
}

$xajax->registerFunction("is_overtime");
function is_overtime($auto_seq,$check){

	$objResponse = new xajaxResponse();

	$mDB = "";
	$mDB = new MywebDB();
	$Qry = "update dispatch_attendance_sub set 
			is_overtime = '$check' 
			where auto_seq = '$auto_seq'";
	$mDB->query($Qry);
	$mDB->remove();
	
    $objResponse->script("oTable = $('#dispatch_attendance_sub_table').dataTable();oTable.fnDraw(false)");

	return $objResponse;
	
}
$xajax->processRequest();



$auto_seq = $_GET['auto_seq'];
$fm = $_GET['fm'];

$mess_title = $title;

$mDB = "";
$mDB = new MywebDB();

$Qry="SELECT a.*,b.work_project FROM dispatch_contract_details a 
LEFT JOIN contract_details b ON b.contract_id = a.contract_id AND b.seq = a.seq
WHERE a.auto_seq = '$auto_seq'";

$mDB->query($Qry);
$total = $mDB->rowCount();
if ($total > 0) {
    //已找到符合資料
	$row=$mDB->fetchRow(2);
	$dispatch_id = $row['dispatch_id'];
	$contract_id = $row['contract_id'];
	$seq = $row['seq'];
	$work_project = $row['work_project'];
}

$mDB->remove();



$m_location		= "/website/smarty/templates/".$site_db."/".$templates;
include $m_location."/sub_modal/project/func08/dispatch_ms/dispatch_attendance_sub.php";


$show_savebtn=<<<EOT
<div class="btn-group vbottom" role="group" style="margin-top:5px;">
	<button id="close" class="btn btn-danger" type="button" onclick="parent.dispatch_contract_details_myDraw();parent.$.fancybox.close();" style="padding: 5px 15px;"><i class="bi bi-power"></i>&nbsp;關閉</button>
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

.field_div1 {width:150px;display: none;font-size:18px;color:#000;text-align:right;font-weight:700;padding:15px 10px 0 0;vertical-align: top;display:inline-block;zoom: 1;*display: inline;}
.field_div2 {width:100%;max-width:200px;display: none;font-size:18px;color:#000;text-align:left;font-weight:700;padding:8px 0 0 0;vertical-align: top;display:inline-block;zoom: 1;*display: inline;}

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

$show_dispatch_attendance_sub_btn=<<<EOT
<div class="btn-group" role="group">
	<button type="button" class="btn btn-danger px-4" onclick="openfancybox_edit('/index.php?ch=dispatch_attendance_sub_add&dispatch_id=$dispatch_id&contract_id=$contract_id&seq=$seq&fm=$fm',800,'96%','');"><i class="bi bi-plus-circle"></i>&nbsp;新增派工人員</button>
	<button type="button" class="btn btn-warning px-4" onclick="openfancybox_edit('/index.php?ch=ch_team&dispatch_id=$dispatch_id&contract_id=$contract_id&seq=$seq&fm=$fm',800,'96%','');"><i class="bi bi-plus-circle"></i>&nbsp;以團隊加入派工人員</button>
	<button type="button" class="btn btn-success text-nowrap px-4" onclick="dispatch_construction_myDraw();"><i class="bi bi-arrow-repeat"></i>&nbsp;重整</button>
</div>
EOT; 


$show_center=<<<EOT
$style_css
<div class="card card_full">
	<div class="card-header text-bg-info">
		<div class="size14 weight float-start" style="margin-top: 5px;">
			$mess_title
		</div>
		<div class="float-end" style="margin-top: -5px;">
			$show_savebtn
		</div>
	</div>
	<div id="full" class="card-body data-overlayscrollbars-initialize py-0 px-3">
		<div id="info_container">
			<form method="post" id="modifyForm" name="modifyForm" enctype="multipart/form-data" action="javascript:void(null);">
			<div class="w-100">
				<div class="field_container3">
					<div class="text-center mt-2">
						<div class="inline size14 weight me-2">合約項次：</div>
						<div class="inline size14 weight blue02 me-2">$seq</div>
						<div class="inline size14 weight text-nowrap">$work_project</div>
					</div>
					<div class="text-center mt-2">
						$show_dispatch_attendance_sub_btn
					</div>
					$show_dispatch_attendance_sub
					<div>
						<input type="hidden" name="fm" value="$fm" />
						<input type="hidden" name="site_db" value="$site_db" />
						<input type="hidden" name="web_id" value="$web_id" />
						<input type="hidden" name="auto_seq" value="$auto_seq" />
					</div>
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
	thisform.submit();
}

function setEdit() {
	$('#close', window.document).addClass("display_none");
	$('#cancel', window.document).removeClass("display_none");
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
/*
$(document).ready(function() {
	$("#employee_content").autoGrow({
		extraLine: true // Adds an extra line at the end of the textarea. Try both and see what works best for you.
	});
});
*/
/*
$(document).ready(async function() {
	//等待其他資源載入完成，此方式適用大部份瀏覽器
	await new Promise(resolve => setTimeout(resolve, 100));
	$('#salary').focus();
});
*/
</script>

EOT;

?>