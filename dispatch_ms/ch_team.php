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
function getchoice($team_id,$dispatch_id,$contract_id,$seq,$def_attendance_start,$def_attendance_end){

	$objResponse = new xajaxResponse();
	
	/*
	$objResponse->script('xajax.config.baseDocument = parent.document;');
	$objResponse->assign("customer_id","value",$customer_id);
	$objResponse->assign("customer_name","innerHTML",$customer_name);
	$objResponse->script('xajax.config.baseDocument = document;');
	*/

	//$dispatch_row = getkeyvalue2("eshop_info","dispatch","dispatch_id = '$dispatch_id'","dispatch_date");
	//$dispatch_date =$dispatch_row['dispatch_date'];

	//存入實體資料庫中
	$mDB = "";
	$mDB = new MywebDB();

	$mDB2 = "";
	$mDB2 = new MywebDB();

	//取得團隊人員名單
	$Qry = "SELECT * FROM team_member where team_id = '$team_id'";
	$mDB->query($Qry);
	if ($mDB->rowCount() > 0) {
		while ($row=$mDB->fetchRow(2)) {
			$employee_id = $row['employee_id'];

			//檢查是否已選取
			$Qry2 = "SELECT auto_seq FROM dispatch_attendance_sub
				WHERE dispatch_id = '$dispatch_id' and contract_id = '$contract_id' and seq = '$seq' and employee_id = '$employee_id'";
			$mDB2->query($Qry2);
			if ($mDB2->rowCount() > 0) {
			} else {
				//不存在則新增
				$Qry2="INSERT INTO dispatch_attendance_sub (dispatch_id,contract_id,seq,employee_id,attendance_start,attendance_end,attendance_hours) VALUES 
					('$dispatch_id','$contract_id','$seq','$employee_id','$def_attendance_start','$def_attendance_end','8')";
				$mDB2->query($Qry2);
			}

		}
	}

    $objResponse->script("parent.dispatch_attendance_sub_myDraw();");
	$message01 = getlang("已新增!");
	$objResponse->script("parent.jAlert('Success', '$message01', 'green', '', 1000);");
    $objResponse->script("parent.$.fancybox.close();");
	
	return $objResponse;
}

$xajax->processRequest();

$fm = $_GET['fm'];
$dispatch_id = $_GET['dispatch_id'];
$contract_id = $_GET['contract_id'];
$seq = $_GET['seq'];
$show_title = getlang("團隊選單");
$Close = getlang("關閉");

$dataTable_de = getDataTable_de();


$card_header_color = "#EFFFBF";


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




$closebtn = "<button class=\"btn btn-danger\" type=\"button\" onclick=\"parent.$.fancybox.close();\" style=\"float:right;margin: 0 5px 0 0;\"><i class=\"bi bi-power\"></i>&nbsp;關閉</button>";


$list_view=<<<EOT
<div class="card card_full">
	<div class="card-header" style="background-color:$card_header_color;">
		$closebtn
		<div class="size14 weight float-start inline" style="margin: 5px 15px 0 0;">
			<div class="inline me-3 text-nowrap">團隊選單</div>
		</div>
	</div>
	<div id="full" class="card-body data-overlayscrollbars-initialize">
		<table class="table table-bordered border-dark w-100" id="choice_table" style="min-width:740px;">
			<thead class="table-light border-dark">
				<tr style="border-bottom: 1px solid #000;">
					<th scope="col" class="text-center text-nowrap" style="width:40%;">選取團隊</th>
					<th scope="col" class="text-center text-nowrap" style="width:30%;">小隊長</th>
					<th scope="col" class="text-center" style="width:30%;">連絡電話</th>
				</tr>
			</thead>
			<tbody class="table-group-divider">
				<tr>
					<td colspan="3" class="dataTables_empty">資料載入中...</td>
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
			"ajaxSource": "/smarty/templates/$site_db/$templates/sub_modal/project/func08/dispatch_ms/server_team.php?site_db=$site_db&team_id=$team_id",
			"language": {
						"sUrl": "$dataTable_de"
					},
			"fnRowCallback": function( nRow, aData, iDisplayIndex ) {
			
			//選取
			var getbtn = "xajax_getchoice('"+aData[0]+"','$dispatch_id','$contract_id','$seq','$def_attendance_start','$def_attendance_end');";
			var m_ch = '<div class="text-center"><button type="button" class="btn btn-primary btn-sm px-4 text-nowrap" style="width: 250px;" onclick="'+getbtn+'">'
				+'<div class="inline text-start" style="width:160px;">'+aData[1]+'</div><div class="inline text-center" style="width:50px;">'+aData[8]+'人</div></button></div>';
			
			$('td:eq(0)', nRow).html( m_ch );
			
			
			$('td:eq(1)', nRow).html( '<div class="text-center size12 weight">'+aData[2]+'</div>');
			$('td:eq(2)', nRow).html( '<div class="text-center size12">'+aData[4]+'</div>');


			return nRow;
			}
					
		});
	
		/* Init the table */
		oTable = $('#choice_table').dataTable();

	} );

</script>
EOT;

?>