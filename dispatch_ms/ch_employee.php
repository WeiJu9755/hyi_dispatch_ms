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
function getchoice($dispatch_id,$contract_id,$seq,$employee_id){

	$objResponse = new xajaxResponse();
	

	$employee_row = getkeyvalue2("eshop_info","employee","employee_id = '$employee_id'","employee_name,company_id,team_id,salary");
	$employee_name = $employee_row['employee_name'];

	$objResponse->script('xajax.config.baseDocument = parent.document;');
	$objResponse->assign("employee_id","value",$employee_id);
	$objResponse->assign("employee_name","value",$employee_name);
	$objResponse->script('xajax.config.baseDocument = document;');


	$message01 = getlang("已選擇!");
	$objResponse->script("jAlert('Success', '$message01', 'green', '', 1000);");
    $objResponse->script("parent.$.fancybox.close();");

	
	return $objResponse;
}


$xajax->processRequest();

$fm = $_GET['fm'];
$dispatch_id = $_GET['dispatch_id'];
$contract_id = $_GET['contract_id'];
$seq = $_GET['seq'];
$show_title = getlang("員工選單");
$Close = getlang("關閉");

$dataTable_de = getDataTable_de();


$closebtn = "<button class=\"btn btn-danger\" type=\"button\" onclick=\"parent.$.fancybox.close();\" style=\"float:right;margin: 0 5px 0 0;\"><i class=\"bi bi-power\"></i>&nbsp;關閉</button>";


$card_header_color = "#EFFFBF";


$list_view=<<<EOT
<div class="card card_full">
	<div class="card-header" style="background-color:$card_header_color;">
		$closebtn
		<div class="size14 weight float-start" style="margin: 5px 15px 0 0;">
			<div class="inline me-3">員工選單</div>
		</div>
	</div>
	<div id="full" class="card-body data-overlayscrollbars-initialize">
		<table class="table table-bordered border-dark w-100" id="choice_table" style="min-width:540px;">
			<thead class="table-light border-dark">
				<tr style="border-bottom: 1px solid #000;">
					<th scope="col" class="text-center text-nowrap" style="width:10%;">選取</th>
					<th scope="col" class="text-center text-nowrap" style="width:15%;">工號</th>
					<th scope="col" class="text-center" style="width:20%;">職務</th>
					<th scope="col" class="text-center" style="width:20%;">姓名</th>
					<th scope="col" class="text-center" style="width:15%;">入職日期</th>
					<th scope="col" class="text-center" style="width:20%;">年資</th>
				</tr>
			</thead>
			<tbody class="table-group-divider">
				<tr>
					<td colspan="6" class="dataTables_empty">資料載入中...</td>
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

<script>
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
			"pageLength": -1,
			"lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
			"pagingType": "full_numbers",  //分页样式： simple,simple_numbers,full,full_numbers
			"searching": true,  //禁用原生搜索
			"ordering": false,
			"ajaxSource": '/smarty/templates/$site_db/$templates/sub_modal/project/func08/dispatch_ms/server_employee.php?site_db=$site_db',
			"language": {
						"sUrl": "$dataTable_de"
					},
			"fnRowCallback": function( nRow, aData, iDisplayIndex ) {

			
			//選取
			var getbtn = "xajax_getchoice('$dispatch_id','$contract_id','$seq','"+aData[0]+"');";
			var m_ch = '<div class="text-center"><button type="button" class="btn btn-primary btn-sm p-0 px-2 m-0 text-nowrap" onclick="'+getbtn+'">選取</button></div>';
			
			$('td:eq(0)', nRow).html( m_ch );
			
			
			$('td:eq(1)', nRow).html( '<div class="text-center size12 weight text-nowrap">'+aData[0]+'</div>');
			$('td:eq(2)', nRow).html( '<div class="text-center size12 text-nowrap">'+aData[17]+'</div>');
			$('td:eq(3)', nRow).html( '<div class="text-center size12 blue01 weight text-nowrap">'+aData[1]+'</div>');


			//計算年資
			var start_date = '';
			var seniority = '';
			if (aData[9] != null && aData[9] != "" && aData[9] != "0000-00-00") {
				start_date = new Date(aData[9]);

				const difference = getDifferenceInYMD(start_date);
				seniority = difference.years+'年'+difference.months+'月'+difference.days+'天';

				$('td:eq(4)', nRow).html( '<div class="text-center size12">'+aData[9]+'</div>');
			} else {
				$('td:eq(4)', nRow).html( '');
			}

			$('td:eq(5)', nRow).html( '<div class="text-center size12">'+seniority+'</div>' );



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

	
</script>
EOT;

?>