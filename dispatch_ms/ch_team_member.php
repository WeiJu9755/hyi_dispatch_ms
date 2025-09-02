<?php

//error_reporting(E_ALL); 
//ini_set('display_errors', '1');

require_once '/website/os/Mobile-Detect-2.8.34/Mobile_Detect.php';
$detect = new Mobile_Detect;

if (!($detect->isMobile() && !$detect->isTablet())) {
	$isMobile = 0;
} else {
	$isMobile = 1;
}


@include_once("/website/class/green_info_class.php");


/* 使用xajax */
@include_once '/website/xajax/xajax_core/xajax.inc.php';
$xajax = new xajax();

$xajax->registerFunction("getchoice");
function getchoice($employee_id,$dispatch_id,$def_attendance_start){

	$objResponse = new xajaxResponse();
	
	/*
	$objResponse->script('xajax.config.baseDocument = parent.document;');
	$objResponse->assign("customer_id","value",$customer_id);
	$objResponse->assign("customer_name","innerHTML",$customer_name);
	$objResponse->script('xajax.config.baseDocument = document;');
	*/

	$dispatch_row = getkeyvalue2("eshop_info","dispatch","dispatch_id = '$dispatch_id'","dispatch_date");
	$dispatch_date =$dispatch_row['dispatch_date'];

	//存入實體資料庫中
	$mDB = "";
	$mDB = new MywebDB();
	
	//先檢查是否已選取了
	$Qry = "select auto_seq from dispatch_member where dispatch_id = '$dispatch_id' and employee_id = '$employee_id'";
	$mDB->query($Qry);
	if ($mDB->rowCount() > 0) {
		$mDB->remove();
		$message01 = getlang("警示");
		$message02 = getlang("此團員已選過了!");
		$objResponse->script("jAlert('$message01', '$message02', 'red', '', 2000);");
		return $objResponse;
		exit;
	}

	//先檢查同一天團隊人員是否已有資料(避免重複)
	$Qry = "SELECT a.auto_seq FROM dispatch_member a
		LEFT JOIN dispatch b ON b.dispatch_id = a.dispatch_id
		WHERE b.dispatch_date = '$dispatch_date' AND a.employee_id = '$employee_id'";
	$mDB->query($Qry);
	if ($mDB->rowCount() > 0) {
		//已存在則不作任何處理
		$mDB->remove();
		$message01 = getlang("警示");
		$message02 = getlang("此團員已在別的出工單中被選過了!");
		$objResponse->script("jAlert('$message01', '$message02', 'red', '', 2000);");
		return $objResponse;
		exit;
	} else {
		$Qry = "insert into dispatch_member (dispatch_id,employee_id,attendance_status,attendance_start) values ('$dispatch_id','$employee_id','正常出勤','$def_attendance_start')";
		$mDB->query($Qry);
	}
	$mDB->remove();
	
    $objResponse->script("parent.dispatch_member_myDraw();");
	$message01 = getlang("已新增!");
	$objResponse->script("jAlert('Success', '$message01', 'green', '', 1000);");
//    $objResponse->script("parent.$.fancybox.close();");
	
	
	return $objResponse;
}

$xajax->registerFunction("join_all");
function join_all($dispatch_id,$team_id,$def_attendance_start){

	$objResponse = new xajaxResponse();
	
	/*
	$objResponse->script('xajax.config.baseDocument = parent.document;');
	$objResponse->assign("customer_id","value",$customer_id);
	$objResponse->assign("customer_name","innerHTML",$customer_name);
	$objResponse->script('xajax.config.baseDocument = document;');
	*/

	$dispatch_row = getkeyvalue2("eshop_info","dispatch","dispatch_id = '$dispatch_id'","dispatch_date");
	$dispatch_date =$dispatch_row['dispatch_date'];

	//存入實體資料庫中
	$mDB = "";
	$mDB = new MywebDB();

	$mDB2 = "";
	$mDB2 = new MywebDB();

	$Qry = "SELECT * FROM team_member where team_id = '$team_id'";
	$mDB->query($Qry);
	if ($mDB->rowCount() > 0) {
		while ($row=$mDB->fetchRow(2)) {
			$employee_id = $row['employee_id'];

			//檢查是否已選取
			$Qry2 = "select auto_seq from dispatch_member where dispatch_id = '$dispatch_id' and employee_id = '$employee_id'";
			$mDB2->query($Qry2);
			if ($mDB2->rowCount() > 0) {
			} else {

				//先檢查同一天團隊人員是否已有資料(避免重複)
				$Qry2 = "SELECT a.auto_seq FROM dispatch_member a
					LEFT JOIN dispatch b ON b.dispatch_id = a.dispatch_id
					WHERE b.dispatch_date = '$dispatch_date' AND a.employee_id = '$employee_id'";
				$mDB2->query($Qry2);
				if ($mDB2->rowCount() > 0) {
					//已存在則不作任何處理
				} else {
					$Qry2 = "insert into dispatch_member (dispatch_id,employee_id,attendance_status,attendance_start) values ('$dispatch_id','$employee_id','正常出勤','$def_attendance_start')";
					$mDB2->query($Qry2);
				}
			}
		}
	}

	$mDB2->remove();
	$mDB->remove();
	
    $objResponse->script("parent.dispatch_member_myDraw();");
	$message01 = getlang("已全部加入!");
	$objResponse->script("jAlert('Success', '$message01', 'green', '', 1000);");
    $objResponse->script("parent.$.fancybox.close();");
	
	return $objResponse;
}


$xajax->processRequest();

$fm = $_GET['fm'];
$dispatch_id = $_GET['dispatch_id'];
$team_id = $_GET['team_id'];
$show_title = getlang("團員選單");
$Close = getlang("關閉");

$dataTable_de = getDataTable_de();


$card_header_color = "#EFFFBF";

//從 $team_id 取得團隊名稱
$team_row = getkeyvalue2($site_db."_info","team","team_id = '$team_id'","team_name");
$team_name =$team_row['team_name'];


//取得 上工時間 預設值
$settings_row = getkeyvalue2($site_db."_info","settings","auto_seq = '1'","def_attendance_start");
if (!empty($settings_row['def_attendance_start']))
	$def_attendance_start = $settings_row['def_attendance_start'];
else
	$def_attendance_start = "";


$closebtn = "<button class=\"btn btn-danger\" type=\"button\" onclick=\"parent.$.fancybox.close();\" style=\"float:right;margin: 0 5px 0 0;\"><i class=\"bi bi-power\"></i>&nbsp;關閉</button>";

$show_fellow_btn=<<<EOT
<div class="btn-group" role="group">
	<button class="btn btn-danger" type="button" onclick="join_all('$dispatch_id','$team_id','$def_attendance_start');"><i class="bi bi-person-check-fill"></i>&nbsp;全部加入</button>
</div>
EOT;


$list_view=<<<EOT
<div class="card card_full">
	<div class="card-header" style="background-color:$card_header_color;">
		$closebtn
		<div class="size14 weight float-start inline" style="margin: 5px 15px 0 0;">
			<div class="inline me-3 text-nowrap">團員選單： $team_name</div>
		</div>
		<div class="float-start">
			$show_fellow_btn
		</div>
	</div>
	<div id="full" class="card-body data-overlayscrollbars-initialize">
		<table class="table table-bordered border-dark w-100" id="choice_table" style="min-width:740px;">
			<thead class="table-light border-dark">
				<tr style="border-bottom: 1px solid #000;">
					<th scope="col" class="text-center text-nowrap" style="width:10%;">選取</th>
					<th scope="col" class="text-center text-nowrap" style="width:10%;">工號</th>
					<th scope="col" class="text-center" style="width:10%;">職務</th>
					<th scope="col" class="text-center" style="width:15%;">姓名</th>
					<th scope="col" class="text-center" style="width:15%;">入職日期</th>
					<th scope="col" class="text-center" style="width:15%;">年資</th>
					<th scope="col" class="text-center" style="width:25%;">備註</th>
				</tr>
			</thead>
			<tbody class="table-group-divider">
				<tr>
					<td colspan="7" class="dataTables_empty">資料載入中...</td>
				</tr>
			</tbody>
		</table>
	</div>
</div>
EOT;



$scroll = true;
if (!($detect->isMobile() && !$detect->isTablet())) {
	$scroll = false;
}



$show_center=<<<EOT

<style>

.card_full {
	width:100%;
	height:100vh;
}

#full {
	width: 100%;
	height: 100%;
}

#choice_table {
	width: 100% !Important;
	margin: 5px 0 0 0 !Important;
}
</style>

$list_view


<script type="text/javascript" charset="utf-8">
	var oTable;
	$(document).ready(function() {
		$('#choice_table').dataTable( {
			"processing": true,
			"serverSide": true,
			"responsive":  {
				details: true
			},//RWD響應式
			"scrollX": '$scroll',
			"paging": true,
			"pageLength": 50,
			"lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
			"pagingType": "full_numbers",  //分页样式： simple,simple_numbers,full,full_numbers
			"searching": true,  //禁用原生搜索
			"ordering": false,
			"ajaxSource": "/smarty/templates/$site_db/$templates/sub_modal/project/func08/dispatch_ms/server_team_member.php?site_db=$site_db&team_id=$team_id",
			"language": {
						"sUrl": "$dataTable_de"
					},
			"fnRowCallback": function( nRow, aData, iDisplayIndex ) {
			
			//選取
			var getbtn = "xajax_getchoice('"+aData[2]+"','$dispatch_id','$def_attendance_start');";
			var m_ch = '<div class="text-center"><button type="button" class="btn btn-primary btn-sm p-0 px-2 m-0" onclick="'+getbtn+'">選取</button></div>';
			
			$('td:eq(0)', nRow).html( m_ch );
			
			
			$('td:eq(1)', nRow).html( '<div class="text-center size12 weight">'+aData[2]+'</div>');
			$('td:eq(2)', nRow).html( '<div class="text-center size12">'+aData[4]+'</div>');
			$('td:eq(3)', nRow).html( '<div class="text-center size12 blue01 weight">'+aData[5]+'</div>');

			//計算年資
			var start_date = '';
			var seniority = '';
			if (aData[6] != null && aData[6] != "" && aData[6] != "0000-00-00") {
				start_date = new Date(aData[6]);

				const difference = getDifferenceInYMD(start_date);
				seniority = difference.years+'年'+difference.months+'月'+difference.days+'天';

				$('td:eq(4)', nRow).html( '<div class="text-center size12">'+aData[6]+'</div>');
			} else {
				$('td:eq(4)', nRow).html( '');
			}

			$('td:eq(5)', nRow).html( '<div class="text-center size12">'+seniority+'</div>' );

			var remark = '';
			if (aData[3] != null && aData[3] != "")
				remark = aData[3];

			$('td:eq(6)', nRow).html( '<div class="text-start size12">'+remark+'</div>');


			return nRow;
			}
					
		});
	
		/* Init the table */
		oTable = $('#choice_table').dataTable();

	} );

// 計算兩個日期之間的年月日差異 (endDate 預設為今天，可不輸入)
function getDifferenceInYMD(startDate, endDate = new Date()) {

    // 取得今天的日期並格式化
    const endYear = endDate.getFullYear();
    const endMonth = endDate.getMonth() + 1; // 月份從0開始，因此要+1
    const endDay = endDate.getDate();

    const end = new Date(endYear, endMonth - 1, endDay);
	
    let years = end.getFullYear() - startDate.getFullYear();
    let months = end.getMonth() - startDate.getMonth();
    let days = end.getDate() - startDate.getDate();

    // 調整月份和年份
    if (days < 0) {
        months--;
        // 獲得前一個月的天數，並調整天數
        const prevMonth = new Date(endDate.getFullYear(), endDate.getMonth(), 0);
        days += prevMonth.getDate();
    }

    if (months < 0) {
        years--;
        months += 12;
    }

    return { years, months, days };
}

function join_all(dispatch_id,team_id,def_attendance_start) {
	xajax_join_all(dispatch_id,team_id,def_attendance_start);
	return false;
}

</script>
EOT;

?>