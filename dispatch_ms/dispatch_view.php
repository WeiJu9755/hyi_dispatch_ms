<?php

//error_reporting(E_ALL); 
//ini_set('display_errors', '1');

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

/*
$xajax->registerFunction("returnValue");
function returnValue($web_id,$project_id,$auth_id,$auto_seq,$tb){
	$objResponse = new xajaxResponse();

	$mDB = "";
	$mDB = new MywebDB();
	
	//從資料庫中讀取圖片資料
	$mDB = "";
	$mDB = new MywebDB();
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

	
	
	$objResponse->assign("files_total".$auto_seq,"innerHTML",$show_files_total);	
	
	
    return $objResponse;
	
}

$xajax->registerFunction("returnTransition_team_name");
function returnTransition_team_name($auto_seq,$team_id){
	$objResponse = new xajaxResponse();

	//取得出勤狀況
	$mDB = "";
	$mDB = new MywebDB();

	$Qry="select team_name from team
	where team_id = '$team_id'
	";
	$mDB->query($Qry);
	
	if ($mDB->rowCount() > 0) {
		$row=$mDB->fetchRow(2);
		$team_name = $row['team_name'];
	}

	$mDB->remove();

	$objResponse->assign("transition_team_name".$auto_seq,"innerHTML",$team_name);

	
    return $objResponse;
}
*/

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
$dispatch_id = $_GET['dispatch_id'];
$pjt = $_GET['pjt'];

$mess_title = $title;

$tb = "dispatch";

$page_title = $title;
$page_description = trim(strip_tags($title));
$page_description = utf8_substr($page_description,0,1024);
$page_keywords = $title;


$mDB = "";
$mDB = new MywebDB();

if (!empty($dispatch_id)) {
	$Qry="SELECT a.*,b.caption,c.company_name,c.short_name FROM dispatch a
	LEFT JOIN projectitem b ON b.project_id = a.project_id AND b.auth_id = a.auth_id
	LEFT JOIN company c ON c.company_id = a.company_id
	WHERE a.dispatch_id = '$dispatch_id'";	
} else {
	$Qry="SELECT a.*,b.caption,c.company_name,c.short_name FROM dispatch a
	LEFT JOIN projectitem b ON b.project_id = a.project_id AND b.auth_id = a.auth_id
	LEFT JOIN company c ON c.company_id = a.company_id
	WHERE a.auto_seq = '$auto_seq'";
}

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
	$contract_id = $row['contract_id'];
	$dispatch_date = $row['dispatch_date'];
	$dispatch_type = $row['dispatch_type'];
	$company_id = $row['company_id'];
	$company_name = $row['company_name'];
	$short_name = $row['short_name'];
	$team_id = $row['team_id'];
	//$team_name = $row['team_name'];
	$o_content = $row['content'];
	$content = nl2br_skip_html(htmlspecialchars_decode($row['content']));
	$task_safety_tips = nl2br_skip_html(htmlspecialchars_decode($row['task_safety_tips']));
	$todolist = nl2br_skip_html(htmlspecialchars_decode($row['todolist']));
	$makeby = $row['makeby'];
	$ConfirmSending = $row['ConfirmSending'];
	$ConfirmSending_datetime = $row['ConfirmSending_datetime'];

	if (!empty($short_name)) {
		$short_company_name = $short_name;
	} else {
		$short_company_name = $company_name;
	}

	if ($ConfirmSending == "Y") {
		$show_ConfirmSending = "<div class=\"border border-success fill-green black weight py-2 px-3 text-center text-nowrap rounded m-auto mt-3\" style=\"width:320px;\"><span class=\"red\">$ConfirmSending_datetime</span> 確認送出</div>";
	} else {
		$show_ConfirmSending = "<div class=\"border border-danger bg-red white weight py-2 px-3 text-center text-nowrap rounded m-auto mt-3\" style=\"width:320px;\">此工單尚未確認送出</div>";
	}


//載入上方索引列模組
@include $m_location."/sub_modal/base/project_index.php";


$show_savebtn=<<<EOT
<div class="btn-group vbottom" role="group" style="margin-top:5px;">
	<button id="close" class="btn btn-danger" type="button" onclick="parent.$.fancybox.close();" style="padding: 5px 15px;"><i class="bi bi-power"></i>&nbsp;關閉</button>
</div>
EOT;


//取得使用者員工身份
$member_picture = getmemberpict50($makeby);

$member_row = getkeyvalue2("memberinfo","member","member_no = '$makeby'","member_name");
$member_name = $member_row['member_name'];

$employee_row = getkeyvalue2($site_db."_info","employee","member_no = '$makeby'","count(*) as manager_count,employee_name,employee_type,team_id");
$manager_count =$employee_row['manager_count'];
$employee_team_id =$employee_row['team_id'];
if ($manager_count > 0) {
	$employee_name = $employee_row['employee_name'];
	$employee_type = $employee_row['employee_type'];
} else {
	$employee_name = $member_name;
	$employee_type = "未在員工名單";
}

$member_logo=<<<EOT
<div class="float-end text-nowrap me-5 size14 weight">
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
	/*max-width: 1400px; !Important;*/
	margin: 0 auto !Important;
}

.field_div1 {width:25%;font-size:18px;color:#000;text-align:right;font-weight:700;padding:15px 10px 0 0;vertical-align: top;display:inline-block;zoom: 1;*display: inline;}
.field_div1a {width:25%;font-size:18px;color:#000;text-align:right;font-weight:700;padding:15px 10px 0 0;vertical-align: top;display:inline-block;zoom: 1;*display: inline;}
.field_div2 {width:100%;max-width:240px;font-size:18px;color:#000;text-align:left;font-weight:700;padding:8px 0 0 0;vertical-align: top;display:inline-block;zoom: 1;*display: inline;}
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
.field_div1a {width:100%;font-size:18px;color:#000;text-align:left;font-weight:700;padding:15px 10px 0 0;vertical-align: top;display:inline-block;zoom: 1;*display: inline;}
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



//圖文檔案列表
$show_attach_list = files_list($isMobile,$site_db,$web_id,$tb,$project_id,$auth_id,'attach',$auto_seq);

//載入功能選單模組
@include $m_location."/sub_modal/base/project_menu.php";




$m_location		= "/website/smarty/templates/".$site_db."/".$templates;
include $m_location."/sub_modal/project/func08/dispatch_ms/dispatch_contract_details_view.php";

include $m_location."/sub_modal/project/func08/dispatch_ms/dispatch_material_details_view.php";


$show_view=<<<EOT

$style_css

<div class="w-100 m-auto p-1 mb-5 bg-white">
	<div style="width:auto;padding: 5px;">
		<div class="inline float-start me-1 mb-2">$left_menu</div>
		<a role="button" class="btn btn-light px-2 py-1 float-start inline me-3 mb-2" href="javascript:void(0);" onClick="parent.history.back();"><i class="bi bi-chevron-left"></i>&nbsp;回上頁</a>
		<a role="button" class="btn btn-light p-1" href="/">回首頁</a>$mess_title
	</div>
	<div class="w-100 size20 pt-1 text-center">$pjt</div>
	<div id="info_container">
		<div class="w-100 mb-5">
			<div class="field_container3">
				<div class="container-fluid">
					<div class="row">
						<div class="col-lg-4 col-sm-12 col-md-12">
							<div class="field_div1a">日期:</div> 
							<div class="field_div2a">
								<div class="inline weight blue02 pt-2 me-2">$dispatch_date</div>
								<div class="inline red pt-2 text-nowrap">(#{$dispatch_id})</div>
							</div> 
						</div> 
						<div class="col-lg-8 col-sm-12 col-md-12">
							<div class="field_div1a">公司:</div> 
							<div class="field_div2a pt-3">
								$short_company_name
							</div> 
							$member_logo
						</div> 
					</div>
				</div>
				$show_ConfirmSending
				<hr class="style_b">
				<div class="container-fluid">
					<div class="row">
						<div class="col-lg-12 col-sm-12 col-md-12">
							<div class="w-100 text-start mb-3">
								<div class="field_div1a size14 weight pe-2 vtop">任務內容：</div>
								<div class="inline size14 text-start m-auto p-3 border border-dark rounded" style="width:100%;max-width: 840px;">$content</div>
							</div> 
							<div class="w-100 text-start mb-3">
								<div class="field_div1a size14 weight pe-2 vtop">工安注意事項：</div>
								<div class="inline size14 text-start m-auto p-3 border border-dark rounded" style="width:100%;max-width: 840px;">$task_safety_tips</div>
							</div> 
							<div class="w-100 text-start">
								<div class="field_div1a size14 weight pe-2 vtop">待辦事項：</div>
								<div class="inline size14 text-start m-auto p-3 border border-dark rounded" style="width:100%;max-width: 840px;">$todolist</div>
							</div> 
						</div> 
					</div>
				</div>
				<hr class="style_b">
				<div class="container-fluid">
					<div class="row">
						<div class="col-lg-12 col-sm-12 col-md-12">
							<div class="field_div1a size14 weight">合約工項/派工：</div> 
							<div style="width:95%;">
								$show_dispatch_contract_details
							</div>
						</div> 
					</div>
				</div>
				<div class="container-fluid mt-3">
					<div class="row">
						<div class="col-lg-12 col-sm-12 col-md-12">
							<div class="field_div1a size14 weight">物料名稱/使用機具：</div> 
							<div style="width:95%;">
								$show_dispatch_material_details
							</div>
						</div> 
					</div>
				</div>
			</div>
		</div>
		$show_attach_list
	</div>
</div>
<script>

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
			art.dialog.tips('已發佈通知!',1);
		},
		"json"
	);
}


</script>

EOT;

} else {


//	$sid = "mbwarning";
	$show_mywarning = mywarning("您所要查看的資料不存在，可能已刪除！");


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
</style>
EOT;


$show_view=<<<EOT

$style_css

<div class="w-100 m-auto p-1 mb-5 bg-white">
	<div style="width:100%;padding: 5px;">
		<div class="inline float-start me-1 mb-2">$left_menu</div>
		<a role="button" class="btn btn-light px-2 py-1 float-start inline me-3 mb-2" href="javascript:void(0);" onClick="parent.history.back();"><i class="bi bi-chevron-left"></i>&nbsp;回上頁</a>
	</div>
	<div class="w-100 size20 pt-1 text-center clearboth">$pjt</div>
	<div id="info_container">
		<div class="w-100 mb-5">
			$show_mywarning
		</div>
	</div>
</div>
EOT;


}

$mDB->remove();

?>