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

if (!($detect->isMobile() && !$detect->isTablet())) {
	$isMobile = "0";
} else {
	$isMobile = "1";
}

@include_once("/website/class/".$site_db."_info_class.php");

/* 使用xajax */
@include_once '/website/xajax/xajax_core/xajax.inc.php';
$xajax = new xajax();

$xajax->registerFunction("DeleteRow");
function DeleteRow($auto_seq,$site_db,$web_id,$project_id,$auth_id,$dispatch_id){

	$objResponse = new xajaxResponse();
	

	//主資料圖片資料夾也一併刪除
	$attach_path0 = "/webdata/".$site_db."/".$web_id."/dispatch/".$project_id."/".$auth_id."/attach".$auto_seq;
	$attach_path1 = "/website".$attach_path0."/";
	if (file_exists($attach_path1)) {
		if (is_dir($attach_path1)) {
			SureRemoveDir($attach_path1,true);
		}
	}

	$mDB = "";
	$mDB = new MywebDB();

	$Qry="select * from dispatch_member	where dispatch_id = '$dispatch_id' order by auto_seq";
	$mDB->query($Qry);
	if ($mDB->rowCount() > 0) {
		while ($row=$mDB->fetchRow(2)) {
			$dispatch_member_auto_seq = $row['auto_seq'];

			//副資料圖片資料夾也一併刪除
			$attach_path0 = "/webdata/".$site_db."/".$web_id."/dispatch_member/".$project_id."/".$auth_id."/attach".$dispatch_member_auto_seq;
			$attach_path1 = "/website".$attach_path0."/";
			if (file_exists($attach_path1)) {
				if (is_dir($attach_path1)) {
					SureRemoveDir($attach_path1,true);
				}
			}
		}
	}

	//刪除 dispatch_attendance_sub 資料
	$Qry="delete from dispatch_attendance_sub where dispatch_id = '$dispatch_id'";
	$mDB->query($Qry);

	//刪除 dispatch_contract_details 資料
	$Qry="delete from dispatch_contract_details where dispatch_id = '$dispatch_id'";
	$mDB->query($Qry);
	
	//刪除主資料
	$Qry="delete from dispatch where auto_seq = '$auto_seq'";
	$mDB->query($Qry);
	
	$mDB->remove();
	
    $objResponse->script("oTable = $('#db_table').dataTable();oTable.fnDraw(false)");
    //$objResponse->script("art.dialog.tips('資料已刪除!',2)");
    //$objResponse->script('Swal.fire({title: "提示",text: "資料已刪除！",icon: "success"});');
	//$objResponse->script("jAlert('提示', '資料已刪除！', 'green', '', 1000);");
	$objResponse->script("autoclose('提示', '資料已刪除！', 1500);");

	return $objResponse;
	
}

$xajax->registerFunction("returnValue");
function returnValue($web_id,$project_id,$auth_id,$auto_seq,$tb,$dispatch_id){
	$objResponse = new xajaxResponse();

	//取得合約工項/派工
	$mDB = "";
	$mDB = new MywebDB();

	$mDB2 = "";
	$mDB2 = new MywebDB();

	$Qry="SELECT a.contract_id,a.seq,a.actual_qty,a.remark,b.work_project,b.unit,b.unit_price,b.contracts_qty FROM dispatch_contract_details a
	LEFT JOIN contract_details b ON b.contract_id = a.contract_id AND b.seq = a.seq
	where a.dispatch_id = '$dispatch_id'
	order by a.auto_seq";
	$mDB->query($Qry);

	$attendance_list = "";

	if ($mDB->rowCount() > 0) {

		while ($row=$mDB->fetchRow(2)) {

			$contract_id = $row['contract_id'];
			$seq = $row['seq'];
			$work_project = $row['work_project'];
			$unit = $row['unit'];
			$unit_price = $row['unit_price'];
			$contracts_qty = $row['contracts_qty'];
			$actual_qty = $row['actual_qty'];
			$remark = $row['remark'];

			$fmt_unit_price = number_format($unit_price);
			$fmt_contracts_qty = number_format($contracts_qty);
			$fmt_actual_qty = number_format($actual_qty);

			$subtotal = round($unit_price*$actual_qty);
			$fmt_subtotal = number_format($subtotal);



$attendance_list.=<<<EOT
<div class="mytable w-100">
	<div class="myrow">
		<div class="mycell" style="width:50%;">
			<div class="mytable w-100">
				<div class="myrow">
					<div class="mycell" style="width:8%;">
						<div class="size12 weight blue02 text-nowrap">$seq</div>
					</div>
					<div class="mycell" style="width:92%;">
						<div class="size12 weight">$work_project</div>
					</div>
				</div>
			</div>
		</div>
		<div class="mycell text-center" style="width:10%;">$unit</div>
		<div class="mycell text-end text-nowrap" style="width:10%;">$fmt_unit_price</div>
		<div class="mycell text-end text-nowrap" style="width:10%;">$fmt_contracts_qty</div>
		<div class="mycell text-end weight red text-nowrap" style="width:10%;">$fmt_actual_qty</div>
		<div class="mycell text-end weight text-nowrap" style="width:10%;">$fmt_subtotal</div>
		<!--
		<div class="mycell" style="width:15%;">$remark</div>
		-->
	</div>
</div>
EOT;

		}

	}


	//取得 物料名稱/使用機具
	$Qry="SELECT a.*,b.* FROM dispatch_material_details a
	LEFT JOIN inventory b ON b.material_no = a.material_no
	where a.dispatch_id = '$dispatch_id'
	order by a.auto_seq";
	$mDB->query($Qry);
	$material_list = "";
	if ($mDB->rowCount() > 0) {
		while ($row=$mDB->fetchRow(2)) {
			$i++;
			$seq = $row['seq'];
			$material_no = $row['material_no'];
			$material_name = $row['material_name'];
			$unit = $row['unit'];
			$warehouse = $row['warehouse'];
			$stock_out_qty = $row['stock_out_qty'];
			$fmt_stock_out_qty = number_format($stock_out_qty);
$material_list.=<<<EOT
<div class="mytable w-100">
	<div class="myrow">
		<div class="mycell" style="width:15%;">$material_no</div>
		<div class="mycell" style="width:40%;">$material_name</div>
		<div class="mycell" style="width:10%;">$unit</div>
		<div class="mycell" style="width:25%;">$warehouse</div>
		<div class="mycell" style="width:10%;">$fmt_stock_out_qty</div>
	</div>
</div>
EOT;

		}
	}

	

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


	
	$objResponse->assign("attendance_list".$auto_seq,"innerHTML",$attendance_list);

	$objResponse->assign("material_list".$auto_seq,"innerHTML",$material_list);

	$objResponse->assign("files_total".$auto_seq,"innerHTML",$show_files_total);	
	
    return $objResponse;
}

$xajax->processRequest();


$fm = $_GET['fm'];
$pjt = $_GET['pjt'];
$project_id = $_GET['project_id'];
$auth_id = $_GET['auth_id'];

$tb = "dispatch";

$m_t = urlencode($_GET['pjt']);

$mess_title = $pjt;


$ShowConfirmSending = $_GET['ShowConfirmSending'];
if ($ShowConfirmSending == "Y") {
	$m_ShowConfirmSending = "checked=\"checked\"";
}



$today = date("Y-m-d");

$dataTable_de = getDataTable_de();
$Prompt = getlang("提示訊息");
$Confirm = getlang("確認");
$Cancel = getlang("取消");

$pubweburl = "//".$domainname;



//網頁標題
$page_title = $pjt;
$page_description = trim(strip_tags($pjt));
$page_description = utf8_substr($page_description,0,1024);
$page_keywords = $pjt;

//載入上方索引列模組
@include $m_location."/sub_modal/base/project_index.php";


$m_pjt = urlencode($_GET['pjt']);

$mk = $_GET['mk'];
$start_date = $_GET['start_date'];
$end_date = $_GET['end_date'];


$today = date("Y-m-d");


$pubweburl = "//".$domainname;


//載入功能選單模組
@include $m_location."/sub_modal/base/project_menu.php";


$fellow_count = 0;
//取得指定管理人數(小隊長)
$pjmyfellow_row = getkeyvalue2($site_db."_info","pjmyfellow","web_id = '$web_id' and project_id = '$project_id' and auth_id = '$auth_id' and pro_id = 'squadleader'","count(*) as fellow_count");
$fellow_count =$pjmyfellow_row['fellow_count'];
if ($fellow_count == 0)
	$fellow_count = "";

/*
$warning_count = 0;
//取得指定管理人數(警訊通知對象)
$pjmyfellow_row = getkeyvalue2($site_db."_info","pjmyfellow","web_id = '$web_id' and project_id = '$project_id' and auth_id = '$auth_id' and pro_id = 'alertlist'","count(*) as warning_count");
$warning_count =$pjmyfellow_row['warning_count'];
if ($warning_count == 0)
	$warning_count = "";
*/

$pjItemManager = false;
//檢查是否為指定管理人(小隊長)
$pjmyfellow_row = getkeyvalue2($site_db."_info","pjmyfellow","web_id = '$web_id' and project_id = '$project_id' and auth_id = '$auth_id' and pro_id = 'squadleader' and member_no = '$memberID'","count(*) as enable_count");
$enable_count =$pjmyfellow_row['enable_count'];
if ($enable_count > 0)
	$pjItemManager = true;


//設定權限
$cando = "N";
if (($powerkey=="A") || ($super_admin=="Y") || ($super_advanced=="Y") || ($pjItemManager == true)) {
	$cando = "Y";
}



//取得使用者員工身份
$member_picture = getmemberpict160($memberID);

$member_row = getkeyvalue2("memberinfo","member","member_no = '$memberID'","member_name");
$member_name = $member_row['member_name'];

$employee_row = getkeyvalue2($site_db."_info","employee","member_no = '$memberID'","count(*) as manager_count,employee_name,employee_type,team_id");
$manager_count =$employee_row['manager_count'];
$team_id = $employee_row['team_id'];
if ($manager_count > 0) {
	$employee_name = $employee_row['employee_name'];
	$employee_type = $employee_row['employee_type'];

	$team_row = getkeyvalue2($site_db."_info","team","team_id = '$team_id'","team_name");
	$team_name = $team_row['team_name'];
} else {
	$employee_name = $member_name;
	$team_name = "未在員工名單";
}


$member_logo=<<<EOT
<div class="mytable bg-white m-auto rounded">
	<div class="myrow">
		<div class="mycell" style="text-align:center;width:73px;padding: 5px 0;">
			<img src="$member_picture" height="75" class="rounded">
		</div>
		<div class="mycell text-start p-2 vmiddle" style="width:107px;">
			<div class="size14 blue02 weight mb-1 text-nowrap">$employee_name</div>
			<div class="size12 weight text-nowrap">$team_name</div>
			<div class="size12 weight text-nowrap">$employee_type</div>
		</div>
	</div>
</div>
EOT;


$show_disabled = "";
$show_disabled_warning = "";
/*
//if ((empty($team_id)) || ((($super_admin=="Y") && ($admin_readonly == "Y")) || (($super_advanced=="Y") && ($advanced_readonly == "Y")))) {
if (((($super_admin=="Y") && ($admin_readonly == "Y")) || (($super_advanced=="Y") && ($advanced_readonly == "Y")))) {
	if ($pjItemManager <> "Y") {
		$show_disabled = "disabled";
		$show_disabled_warning = "<div class=\"size12 red weight text-center p-2\">此區為小隊長專區，非經授權請勿進行任何處理</div>";
	}
}
*/

if ($cando == "Y") {
	if (($super_admin == "Y") && ($admin_readonly == "Y")) {
		$show_disabled = "disabled";
		$show_disabled_warning = "<div class=\"size12 red weight text-center p-2\">此區為授權專區，非經授權請勿進行任何處理</div>";
	} else if (($super_advanced == "Y") && ($advanced_readonly == "Y")) {
		$show_disabled = "disabled";
		$show_disabled_warning = "<div class=\"size12 red weight text-center p-2\">此區為授權專區，非經授權請勿進行任何處理</div>";
	}
}


$show_admin_list = "";


if ($cando == "Y") {

	$show_modify_btn = "";


	if (($powerkey == "A") || (($super_admin=="Y") && ($admin_readonly <> "Y"))) {
$show_admin_list=<<<EOT
<div class="text-center">
	<div class="btn-group me-2 mb-2" role="group">
		<a role="button" class="btn btn-light" href="javascript:void(0);" onclick="openfancybox_edit('/index.php?ch=fellowlist&project_id=$project_id&auth_id=$auth_id&pro_id=squadleader&t=指定管理人&fm=base',850,'96%',true);" title="指定管理人"><i class="bi bi-shield-fill-check size14 red inline me-2 vmiddle"></i><div class="inline size12 me-2">指定管理人</div><div class="inline red weight vmiddle">$fellow_count</div></a>
	</div>
</div>
EOT;
	}

$show_modify_btn=<<<EOT
<div class="text-center my-2">
	<div class="btn-group me-2 mb-2" role="group">
		<button $show_disabled type="button" class="btn btn-danger text-nowrap" onclick="openfancybox_edit('/index.php?ch=add&project_id=$project_id&auth_id=$auth_id&t=$t&fm=$fm',1800,'96%','');"><i class="bi bi-plus-circle"></i>&nbsp;新增資料</button>
		<button type="button" class="btn btn-success text-nowrap" onclick="myDraw();"><i class="bi bi-arrow-repeat"></i>&nbsp;重整</button>
		<button type="button" class="btn btn-warning text-nowrap" onclick="add_shortcuts('$site_db','$web_id','$templates','$project_id','$auth_id','$pjcaption','$i_caption','$fm','$memberID');"><i class="bi bi-lightning-fill red"></i>&nbsp;加入至快捷列</button>
	</div>
	<!--
	<div class="btn-group mb-2" role="group">
		<a $show_disabled role="button" class="btn btn-success text-nowrap" href="/index.php?ch=dispatch_day_summary&project_id=$project_id&auth_id=$auth_id&fm=$fm" target="_blank"><i class="bi bi-printer"></i>&nbsp;出工日報表</a>
	</div>
	-->
</div>
$show_admin_list
EOT;


		if (!($detect->isMobile() && !$detect->isTablet())) {

$show_ConfirmSending_btn=<<<EOT
	<div class="size14" style="width:150px;position: relative;margin: 27px 0 -27px 200px;z-index:999;">
		<input type="checkbox" class="inputtext" name="ShowConfirmSending" id="ShowConfirmSending" value="Y" $m_ShowConfirmSending pjt="$m_pjt" project_id= "$project_id" auth_id="$auth_id"/>
		<label for="ShowConfirmSending" style="cursor:pointer;">顯示已確認資料</label>
	</div>
EOT;

		} else {

$show_ConfirmSending_btn=<<<EOT
	<div class="size14 w-200 text-center m-auto mb-3" style="z-index:999;">
		<input type="checkbox" class="inputtext" name="ShowConfirmSending" id="ShowConfirmSending" value="Y" $m_ShowConfirmSending pjt="$m_pjt" project_id= "$project_id" auth_id="$auth_id"/>
		<label for="ShowConfirmSending" style="cursor:pointer;">顯示已確認資料</label>
	</div>
EOT;

		}




$list_view=<<<EOT
<div class="w-100 m-auto p-1 mb-5 bg-white">
	<div style="width:auto;padding: 5px;">
		<div class="inline float-start me-1 mb-2">$left_menu</div>
		<a role="button" class="btn btn-light px-2 py-1 float-start inline me-3 mb-2" href="javascript:void(0);" onClick="parent.history.back();"><i class="bi bi-chevron-left"></i>&nbsp;回上頁</a>
		<a role="button" class="btn btn-light p-1" href="/">回首頁</a>$mess_title
	</div>
	<div class="container-fluid">
		<div class="row">
			<div class="col-lg-2 col-sm-12 col-md-12 p-1 d-flex flex-column justify-content-center align-items-center">
				$member_logo
			</div> 
			<div class="col-lg-8 col-sm-12 col-md-12 p-1">
				<div class="size20 pt-1 text-center">$pjt</div>
				$show_modify_btn
				$show_disabled_warning
			</div> 
			<div class="col-lg-2 col-sm-12 col-md-12">
			</div> 
		</div>
	</div>
	$show_ConfirmSending_btn
	<table class="table table-bordered border-dark w-100" id="db_table" style="min-width:1200px;">
		<thead class="table-light border-dark">
			<tr style="border-bottom: 1px solid #000;">
				<th class="text-center text-nowrap" style="width:8%;padding: 10px;background-color: #CBF3FC;">工單日期/單號</th>
				<th class="text-center text-nowrap" style="width:8%;padding: 10px;background-color: #CBF3FC;">合約簡稱</th>
				<th class="text-center text-nowrap" style="width:40%;padding: 10px;background-color: #CBF3FC;">合約工項/派工</th>
				<th class="text-center text-nowrap" style="width:20%;padding: 10px;background-color: #CBF3FC;">物料名稱/使用機具</th>
				<th class="text-center text-nowrap" style="width:8%;padding: 10px;background-color: #CBF3FC;">附檔</th>
				<th class="text-center text-nowrap" style="width:8%;padding: 10px;background-color: #CBF3FC;">處理</th>
				<th class="text-center text-nowrap" style="width:8%;padding: 10px;background-color: #CBF3FC;">最後修改</th>
			</tr>
		</thead>
		<tbody class="table-group-divider">
			<tr>
				<td colspan="7" class="dataTables_empty">資料載入中...</td>
			</tr>
		</tbody>
	</table>
</div>
EOT;



$scroll = true;
if (!($detect->isMobile() && !$detect->isTablet())) {
	$scroll = false;
}
	
	
$show_view=<<<EOT

<style type="text/css">
#db_table {
	width: 100% !Important;
	margin: 5px 0 0 0 !Important;
}

</style>

$list_view

<script type="text/javascript" charset="utf-8">
	var oTable;
	$(document).ready(function() {
		$('#db_table').dataTable( {
			"processing": true,
			"serverSide": true,
			"responsive":  {
				details: true
			},//RWD響應式
			"scrollX": '$scroll',
			/*"scrollY": 600,*/
			"paging": true,
			"pageLength": 50,
			"lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
			"pagingType": "full_numbers",  //分页样式： simple,simple_numbers,full,full_numbers
			"searching": true,  //禁用原生搜索
			"ordering": false,
			"ajaxSource": "/smarty/templates/$site_db/$templates/sub_modal/project/func08/dispatch_ms/server_dispatch.php?site_db=$site_db&web_id=$web_id&project_id=$project_id&auth_id=$auth_id&ShowConfirmSending=$ShowConfirmSending",
			"language": {
						"sUrl": "$dataTable_de"
						/*"sUrl": '//cdn.datatables.net/plug-ins/1.12.1/i18n/zh-HANT.json'*/
					},
			"fixedHeader": true,
			"fixedColumns": {
        		left: 1,
    		},
			"fnRowCallback": function( nRow, aData, iDisplayIndex ) { 


				var preview = "/index.php?ch=view&pjt=$pjt&auto_seq="+aData[10]+"&project_id="+aData[5]+"&auth_id="+aData[6]+"&fm=$fm#myScrollspy";

				var attendance_list = '<div id="attendance_list'+aData[10]+'"></div>';
				var material_list = '<div id="material_list'+aData[10]+'"></div>';
				var files_total = '<div class="d-flex justify-content-center align-items-center size12 text-center mt-2" id="files_total'+aData[10]+'"></div>';

				xajax_returnValue('$web_id','$project_id','$auth_id',aData[10],'dispatch',aData[7]);

				
				//moment.locale('zh-tw');
				//var ago = '<div class="red text-center">('+moment(aData[3],"YYYYMMDD").fromNow()+')</div>';
			
				//工單日期/單號
				$('td:eq(0)', nRow).html( '<a href="'+preview+'"><div class="text-center text-nowrap size14 blue02 weight">'+aData[0]+'  <i class="bi bi-box-arrow-up-right size06 vtop"></i></div></a><div class="text-center text-nowrap size10 red weight">(#'+aData[7]+')</div>' );
				//$('td:eq(0)', nRow).html( '<div class="text-center text-nowrap size14 blue02 weight">'+aData[0]+'</div><div class="text-center text-nowrap size10 red weight">(#'+aData[7]+')</div>' );

				//合約簡稱
				var contract_abbreviation = "";
				if (aData[3] != null && aData[3] != "") {
					contract_abbreviation = aData[3];
				} else {
					if (aData[2] != null && aData[2] != "")
						contract_abbreviation = aData[2];
				}

				$('td:eq(1)', nRow).html( '<div class="d-flex justify-content-center align-items-center size14 weight text-center" style="height:auto;min-height:40px;">'+contract_abbreviation+'</div>' );

				//合約工項/派工
				$('td:eq(2)', nRow).html( attendance_list );

				//物料名稱/使用機具
				$('td:eq(3)', nRow).html( material_list );


				var mdel = "myDel("+aData[10]+",'$site_db','$web_id','$project_id','$auth_id','"+aData[7]+"');";

			

				if (aData[8] == "Y") {

	
					//$('td', nRow).css('background-color', '#FFE8E8');
					$('td', nRow).css('background-color', '#ddd');

					var ConfirmSending_datetime = "";
					if (aData[9] != null && aData[9] != "")
						ConfirmSending_datetime = aData[9].substr(0, 16);

					if (('$powerkey' == "A") || ('$super_admin' == "Y") || ('$super_advanced' == "Y")) {

						var url1 = "openfancybox_edit('/index.php?ch=edit&auto_seq="+aData[10]+"&readonly=N&fm=$fm',1800,'96%','');";
						var url3 = "openfancybox_edit('/index.php?tb=$tb&auto_seq="+aData[10]+"&project_id=$project_id&auth_id=$auth_id&readonly=N&fm=pjattach','96%','96%','myDraw();');";

						$('td:eq(4)', nRow).html( '<a href="javascript:void(0);" onclick="'+url3+'" title="上傳檔案">'+files_total+'</a>' );

						var show_ConfirmSending = '<div class="size12 red weight text-nowrap">'+ConfirmSending_datetime+'</div>'
								+'<div class="size12">確認送出'
								+'&nbsp;&nbsp;<a href="javascript:vpid(0);" onclick="'+url1+'" title="修改"><i class="bi bi-pencil-square"></i></a>'
								+'&nbsp;&nbsp;<a href="javascript:vpid(0);" onclick="'+url3+'" title="上傳檔案"><i class="bi bi-file-arrow-up"></i></a>'
								+'&nbsp;&nbsp;<a href="javascript:vpid(0);" onclick="'+mdel+'" title="刪除"><i class="bi bi-trash"></i></a>'
								+'</div>';

					} else {

						var url1 = "openfancybox_edit('/index.php?ch=edit&auto_seq="+aData[10]+"&readonly=Y&fm=$fm',1800,'96%','');";
						var url3 = "openfancybox_edit('/index.php?tb=$tb&auto_seq="+aData[10]+"&project_id=$project_id&auth_id=$auth_id&readonly=Y&fm=pjattach','96%','96%','myDraw();');";

						$('td:eq(4)', nRow).html( '<a href="javascript:void(0);" onclick="'+url3+'" title="上傳檔案">'+files_total+'</a>' );

						var show_ConfirmSending = '<div class="size12 red weight text-nowrap">'+ConfirmSending_datetime+'</div>'
								+'<div class="size12">確認送出'
								+'&nbsp;&nbsp;<a href="javascript:vpid(0);" onclick="'+url1+'" title="修改"><i class="bi bi-pencil-square"></i></a>'
								+'&nbsp;&nbsp;<a href="javascript:vpid(0);" onclick="'+url3+'" title="上傳檔案"><i class="bi bi-file-arrow-up"></i></a>'
								+'</div>';
					}

					$('td:eq(5)', nRow).html( '<div class="text-center">'+show_ConfirmSending+'</div>' );

				} else {

					var url1 = "openfancybox_edit('/index.php?ch=edit&auto_seq="+aData[10]+"&fm=$fm',1800,'96%','');";
			
					var show_btn = '';

					var url3 = "openfancybox_edit('/index.php?tb=$tb&auto_seq="+aData[10]+"&project_id=$project_id&auth_id=$auth_id&readonly=N&fm=pjattach','96%','96%','myDraw();');";
					$('td:eq(4)', nRow).html( '<a href="javascript:void(0);" onclick="'+url3+'" title="上傳檔案">'+files_total+'</a>' );

					show_btn = '<div class="btn-group text-nowrap">'
							+'<button type="button" class="btn btn-light" onclick="'+url1+'" title="修改"><i class="bi bi-pencil-square"></i></button>'
							+'<button type="button" class="btn btn-light" onclick="'+url3+'" title="上傳檔案"><i class="bi bi-file-arrow-up"></i></button>'
							+'<button type="button" class="btn btn-light" onclick="'+mdel+'" title="刪除"><i class="bi bi-trash"></i></button>'
							+'</div>';

					$('td:eq(5)', nRow).html( '<div class="d-flex justify-content-center align-items-center text-center" style="height:auto;min-height:40px;">'+show_btn+'</div>' );


				}

				//最後修改
				var last_modify = "";
				if (aData[15] != null && aData[15] != "")
					last_modify = '<div class="text-nowrap">'+moment(aData[15]).format('YYYY-MM-DD HH:mm')+'</div>';
				
				//編輯人員
				var member_name = "";
				if (aData[14] != null && aData[14] != "")
					member_name = '<div class="text-nowrap">'+aData[14]+'</div>';

				$('td:eq(6)', nRow).html( '<div class="justify-content-center align-items-center size12 text-center" style="height:auto;min-height:40px;">'+last_modify+member_name+'</div>' );



				return nRow;
			
			}
			
		});
	
		/* Init the table */
		oTable = $('#db_table').dataTable();
		
	} );

var myDel = function(auto_seq,site_db,web_id,project_id,auth_id,dispatch_id){				

	Swal.fire({
	title: "您確定要刪除此筆資料嗎?",
	text: "此項作業會刪除所有與此筆記錄有關的資料及圖檔",
	icon: "question",
	showCancelButton: true,
	confirmButtonColor: "#3085d6",
	cancelButtonColor: "#d33",
	cancelButtonText: "取消",
	confirmButtonText: "確定"
	}).then((result) => {
		if (result.isConfirmed) {
			myDel2(auto_seq,site_db,web_id,project_id,auth_id,dispatch_id);
		}
	});

};

var myDel2 = function(auto_seq,site_db,web_id,project_id,auth_id,dispatch_id){				

	Swal.fire({
	title: "請再確定是否要刪除此筆資料?",
	text: "確定後將刪除所有與此筆記錄有關的資料及圖檔",
	icon: "warning",
	showCancelButton: true,
	confirmButtonColor: "#3085d6",
	cancelButtonColor: "#d33",
	cancelButtonText: "取消",
	confirmButtonText: "刪除"
	}).then((result) => {
		if (result.isConfirmed) {
			xajax_DeleteRow(auto_seq,site_db,web_id,project_id,auth_id,dispatch_id);
		}
	});

};


var myDraw = function(){
	var oTable;
	oTable = $('#db_table').dataTable();
	oTable.fnDraw(false);
}

	
</script>

<script>

    $(function(){
      // bind change event to select
	  //$("#ShowConfirmSending").click(function(){
      $('#ShowConfirmSending').bind('change', function () {
          var pjt = $(this).attr('pjt'); // get selected value
          var project_id = $(this).attr('project_id'); // get selected value
          var auth_id = $(this).attr('auth_id'); // get selected value
		  var ShowConfirmSending = $(this).is(":checked");
		  if (ShowConfirmSending == true) 
			  ShowConfirmSending = 'Y';
		  else
		  	ShowConfirmSending = 'N';

		  //alert(ShowConfirmSending);
		  
		  var url = '/index.php?pjt='+pjt+'&project_id='+project_id+'&auth_id='+auth_id+'&ShowConfirmSending='+ShowConfirmSending+'&fm=dispatch#myScrollspy';

          if (url) { // require a URL
              window.location = url; // redirect
          }
		  
          return false;
      });
    });

</script>

EOT;

} else {

	$sid = "mbwarning";
	$show_view = mywarning("很抱歉! 目前此功能只開放給本站特定會員，或是您目前的權限無法存取此頁面。");

}

?>