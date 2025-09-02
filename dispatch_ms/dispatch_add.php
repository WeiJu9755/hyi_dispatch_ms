<?php

session_start();
$memberID = $_SESSION['memberID'];
$powerkey = $_SESSION['powerkey'];


require_once '/website/os/Mobile-Detect-2.8.34/Mobile_Detect.php';
$detect = new Mobile_Detect;


@include_once("/website/class/".$site_db."_info_class.php");

/* 使用xajax */
@include_once '/website/xajax/xajax_core/xajax.inc.php';
$xajax = new xajax();

$xajax->registerFunction("processform");

function processform($aFormValues){

	$objResponse = new xajaxResponse();
	
//   	$objResponse->alert("formData: " . print_r($aFormValues, true));
//   	$objResponse->alert("formData: " . print_r($_POST, true));
	
	$powerkey = trim($aFormValues['powerkey']);
	$super_admin = trim($aFormValues['super_admin']);
	$admin_readonly = trim($aFormValues['admin_readonly']);
	
	$bError = false;
	
	$today = date("Y-m-d");


		//if (($powerkey == "A") || (($super_admin=="Y") && ($admin_readonly <> "Y"))) {

		//} else {

			if (trim($aFormValues['dispatch_date']) == "")	{
				$objResponse->script("jAlert('警示', '請輸入日期', 'red', '', 2000);");
				return $objResponse;
				exit;
			}
			/*
			if (trim($aFormValues['dispatch_date']) < $today)	{
				$objResponse->script("jAlert('警示', '日期不可小於今天', 'red', '', 2000);");
				return $objResponse;
				exit;
			}
			*/
			if (trim($aFormValues['contract_id']) == "")	{
				$objResponse->script("jAlert('警示', '請選擇合約', 'red', '', 2000);");
				return $objResponse;
				exit;
			}
		//}


	/*
	if (trim($aFormValues['company_id']) == "")	{
		$objResponse->script("jAlert('警示', '請輸入公司', 'red', '', 2000);");
		return $objResponse;
		exit;
	}
		*/

	//自動產生 dispatch_id
	$today = date('Ymd',strtotime(trim($aFormValues['dispatch_date'])));

	$mDB = "";
	$mDB = new MywebDB();
	
	//取得最後的群組代號
	$Qry = "SELECT dispatch_id FROM dispatch WHERE SUBSTRING(dispatch_id,1,8) = '$today' ORDER BY dispatch_id DESC LIMIT 0,1";
	$mDB->query($Qry);
	if ($mDB->rowCount() > 0) {
		$row=$mDB->fetchRow(2);
		$temp_dispatch_id = $row['dispatch_id'];
		$str4 = substr($temp_dispatch_id,-4,4);
		$num = (int)$str4+1;
		$filled_int = sprintf("%04d", $num);
		$new_dispatch_id = $today.$filled_int;
	} else {
		$new_dispatch_id = $today."0001";
	}
	
	
	/*
	$Qry = "select dispatch_id from dispatch order by dispatch_id desc limit 0,1";
	$mDB->query($Qry);
	if ($mDB->rowCount() > 0) {
		$row=$mDB->fetchRow(2);
		$temp_dispatch_id = $row['dispatch_id'];
		if (substr($temp_dispatch_id,0,3) == "HDF"){
			$str7 = substr($temp_dispatch_id,3,7);
			$num = (int)$str7+1;
			$filled_int = sprintf("%07d", $num);
			$new_dispatch_id = "HDF".$filled_int;
	
		} else {
			$new_dispatch_id = "HDF0000001";
		}
	} else {
		$new_dispatch_id = "HDF0000001";
	}
	*/

	
	if (!$bError) {
		$fm					= trim($aFormValues['fm']);
		$site_db			= trim($aFormValues['site_db']);
		$templates			= trim($aFormValues['templates']);
		$web_id				= trim($aFormValues['web_id']);
		$project_id			= trim($aFormValues['project_id']);
		$auth_id			= trim($aFormValues['auth_id']);
		$dispatch_date		= trim($aFormValues['dispatch_date']);
		$company_id			= trim($aFormValues['company_id']);
		//$team_id			= trim($aFormValues['team_id']);
		$member_no			= trim($aFormValues['member_no']);
		$contract_id		= trim($aFormValues['contract_id']);
		

		
		//存入實體資料庫中
		//$mDB = "";
		//$mDB = new MywebDB();
	  
		$Qry="insert into dispatch (web_id,project_id,auth_id,dispatch_id,dispatch_date,company_id,contract_id,makeby,create_date,last_modify) values ('$web_id','$project_id','$auth_id','$new_dispatch_id','$dispatch_date','$company_id','$contract_id','$member_no',now(),now())";
		$mDB->query($Qry);
		//再取出auto_seq
		$Qry="select auto_seq from dispatch where dispatch_id = '$new_dispatch_id' order by auto_seq desc limit 0,1";
		$mDB->query($Qry);
		if ($mDB->rowCount() > 0) {
			//已找到符合資料
			$row=$mDB->fetchRow(2);
			$auto_seq = $row['auto_seq'];
		}

		/*
		//取得 上工時間 預設值
		$settings_row = getkeyvalue2($site_db."_info","settings","auto_seq = '1'","def_attendance_start");
		if (!empty($settings_row['def_attendance_start']))
			$def_attendance_start = $settings_row['def_attendance_start'];
		else
			$def_attendance_start = "";
		*/
		/*
		$mDB2 = "";
		$mDB2 = new MywebDB();

		//工單新增完成，自動將所有團隊人員加入至工單中
		//$Qry = "SELECT * FROM team_member where team_id = '$team_id'";
		$Qry = "SELECT a.*,b.construction_id FROM team_member a
				LEFT JOIN team b ON b.team_id = a.team_id
				WHERE a.team_id = '$team_id'";
		$mDB->query($Qry);
		if ($mDB->rowCount() > 0) {
			while ($row=$mDB->fetchRow(2)) {
				$employee_id = $row['employee_id'];
				$construction_id = $row['construction_id'];
	
				//先檢查同一天團隊人員是否已有資料(避免重複)
				$Qry2 = "SELECT a.auto_seq FROM dispatch_member a
					LEFT JOIN dispatch b ON b.dispatch_id = a.dispatch_id
					WHERE b.dispatch_date = '$dispatch_date' AND a.employee_id = '$employee_id'";
				$mDB2->query($Qry2);
				if ($mDB2->rowCount() > 0) {
					//已存在則不作任何處理
				} else {
					$Qry2 = "insert into dispatch_member (dispatch_id,employee_id,attendance_status,construction_id,attendance_start) values ('$new_dispatch_id','$employee_id','正常出勤','$construction_id','$def_attendance_start')";
					$mDB2->query($Qry2);
				}
				
			}
		}
	
        $mDB2->remove();
		*/

        $mDB->remove();

		if (!empty($auto_seq)) {
			$objResponse->script("myDraw();");
			//$objResponse->script("art.dialog.tips('已新增，請繼續輸入其他資料...',2);");
			$objResponse->script("window.location='/?ch=edit&auto_seq=$auto_seq&fm=$fm';");
		} else {
			//$objResponse->script("art.dialog.alert('發生不明原因的錯誤，資料未新增，請再試一次!');");
			$objResponse->script("parent.$.fancybox.close();");
		}
	};
	
	return $objResponse;	
}

$xajax->processRequest();

$fm = $_GET['fm'];
$project_id = $_GET['project_id'];
$auth_id = $_GET['auth_id'];
$t = $_GET['t'];


if ($fm == "dispatch") {
	$default_day = date("Y-m-d");
} else {
	$default_day = date("Y-m-d", strtotime("+1 day"));
}

$mess_title = $title;

$super_admin = "N";
$mem_row = getkeyvalue2('memberinfo','member',"member_no = '$memberID'",'admin,admin_readonly');
$super_admin = $mem_row['admin'];
$admin_readonly = $mem_row['admin_readonly'];



//從 $memberID 取得員工所屬公司
$employee_row = getkeyvalue2($site_db."_info","employee","member_no = '$memberID'","company_id,team_id");
$company_id =$employee_row['company_id'];
$team_id =$employee_row['team_id'];

//取得公司名稱
$company_row = getkeyvalue2($site_db."_info","company","company_id = '$company_id'","company_name");
$company_name =$company_row['company_name'];

$cando = true;
if (empty($company_id)) {
	$cando = false;
	$sid = "view01";
	$show_center = mywarning2("很抱歉! 您的員工身分未指定公司，無法進行本項作業。",'<button type="button" class="btn btn-primary" onclick="parent.$.fancybox.close();">關閉</button>');
} else if (empty($team_id)) {
	$cando = false;
	$sid = "view01";
	$show_center = mywarning2("很抱歉! 您的員工身分未指定團隊，無法進行本項作業。",'<button type="button" class="btn btn-primary" onclick="parent.$.fancybox.close();">關閉</button>');
}

if ($cando == true) {


$mDB = "";
$mDB = new MywebDB();

//載入所有合約
$Qry="SELECT contract_id,contract_abbreviation,contract_caption FROM contract WHERE closed <> 'Y' order by auto_seq";
$mDB->query($Qry);
$select_contract = "";
$select_contract .= "<option></option>";

if ($mDB->rowCount() > 0) {
	while ($row=$mDB->fetchRow(2)) {
		$ch_contract_id = $row['contract_id'];
		$ch_contract_abbreviation = $row['contract_abbreviation'];
		$ch_contract_caption = $row['contract_caption'];
		$select_contract .= "<option value=\"$ch_contract_id\" ".mySelect($ch_contract_id,$contract_id).">$ch_contract_id &nbsp;&nbsp; 【{$ch_contract_abbreviation}】 &nbsp;&nbsp; $ch_contract_caption</option>";
	}
}
/*
//載入所有團隊
$Qry="select team_id,team_name from team order by auto_seq";
$mDB->query($Qry);
$select_team = "";
$select_team .= "<option></option>";

if ($mDB->rowCount() > 0) {
	while ($row=$mDB->fetchRow(2)) {
		$ch_team_id = $row['team_id'];
		$ch_team_name = $row['team_name'];
		$select_team .= "<option value=\"$ch_team_id\" ".mySelect($ch_team_id,$team_id).">$ch_team_name $ch_team_id</option>";
	}
}
*/
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
	width: 1000px !Important;
	margin: 0 auto !Important;
}

.field_div1 {width:150px;display: none;font-size:18px;color:#000;text-align:right;font-weight:700;padding:15px 10px 0 0;vertical-align: top;display:inline-block;zoom: 1;*display: inline;}
.field_div2 {width:100%;max-width:800px;display: none;font-size:18px;color:#000;text-align:left;font-weight:700;padding:8px 0 0 0;vertical-align: top;display:inline-block;zoom: 1;*display: inline;}

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


$show_center=<<<EOT
$style_css
<div class="card card_full">
	<div class="card-header text-bg-info">
		<div class="size14 weight float-start">
			$mess_title
		</div>
	</div>
	<div id="full" class="card-body data-overlayscrollbars-initialize">
		<div id="info_container">
			<form method="post" id="addForm" name="addForm" enctype="multipart/form-data" action="javascript:void(null);">
				<div class="field_container3">
					<div>
						<div class="field_div1">日期:</div> 
						<div class="field_div2">
							<div class="input-group maxwidth" id="dispatch_date" >
								<input type="text" class="form-control" name="dispatch_date" placeholder="請輸入點工日期" aria-describedby="dispatch_date" value="$default_day">
								<button class="btn btn-outline-secondary input-group-append input-group-addon" type="button" data-target="#dispatch_date" data-toggle="datetimepicker"><i class="bi bi-calendar"></i></button>
							</div>
							<script type="text/javascript">
								$(function () {
									$('#dispatch_date').datetimepicker({
										locale: 'zh-tw'
										,format:"YYYY-MM-DD"
										,allowInputToggle: true
									});
								});
							</script>
						</div>
					</div>
					<div>
						<div class="field_div1">公司:</div> 
						<div class="field_div2 pt-3">
							$company_id $company_name
							<!--
							<select id="company_id" name="company_id" placeholder="請選擇公司" style="width:100%;max-width:550px;">
								$select_company
							</select>
							-->
						</div> 
					</div>
					<!--
					<div>
						<div class="field_div1">團隊:</div> 
						<div class="field_div2 pt-3">
							<select id="team_id" name="team_id" placeholder="請選擇團隊" style="width:100%;max-width:250px;">
								$select_team
							</select>
						</div> 
					</div>
					-->
					<div>
						<div class="field_div1">合約:</div> 
						<div class="field_div2 pt-3">
							<select id="contract_id" name="contract_id" placeholder="請選擇合約" style="width:100%;max-width:550px;">
								$select_contract
							</select>
						</div> 
					</div>
				</div>
				<div class="form_btn_div mt-5">
					<input type="hidden" name="fm" value="$fm" />
					<input type="hidden" name="powerkey" value="$powerkey" />
					<input type="hidden" name="super_admin" value="$super_admin" />
					<input type="hidden" name="admin_readonly" value="$admin_readonly" />
					<input type="hidden" name="site_db" value="$site_db" />
					<input type="hidden" name="templates" value="$templates" />
					<input type="hidden" name="web_id" value="$web_id" />
					<input type="hidden" name="project_id" value="$project_id" />
					<input type="hidden" name="auth_id" value="$auth_id" />
					<input type="hidden" name="member_no" value="$memberID" />
					<input type="hidden" name="company_id" value="$company_id" />
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
	oTable = parent.$('#db_table').dataTable();
	oTable.fnDraw(false);
}
	
</script>
EOT;

}

?>