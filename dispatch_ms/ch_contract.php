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

//載入公用函數
@include_once '/website/include/pub_function.php';


@include_once("/website/class/green_info_class.php");


/* 使用xajax */
@include_once '/website/xajax/xajax_core/xajax.inc.php';
$xajax = new xajax();

$xajax->registerFunction("getchoice");
function getchoice($dispatch_id,$contract_id,$seq){

	$objResponse = new xajaxResponse();
	
	/*
	$objResponse->script('xajax.config.baseDocument = parent.document;');
	$objResponse->assign("customer_id","value",$customer_id);
	$objResponse->assign("customer_name","innerHTML",$customer_name);
	$objResponse->script('xajax.config.baseDocument = document;');
	*/

	//$objResponse->script("jAlert('test', '$construction_id $dispatch_id', 'red', '', 2000);");
	//return $objResponse;
	//exit;
	

	//存入實體資料庫中
	$mDB = "";
	$mDB = new MywebDB();

	
	//先檢查是否已選取了
	$Qry = "select auto_seq from dispatch_contract_details where dispatch_id = '$dispatch_id' and contract_id = '$contract_id' and seq = '$seq'";

	$mDB->query($Qry);
	if ($mDB->rowCount() > 0) {
		$mDB->remove();
		$message01 = getlang("警示");
		$message02 = getlang("此工作項目已重複選取了!");
		$objResponse->script("jAlert('$message01', '$message02', 'red', '', 2000);");
		return $objResponse;
		exit;
	}
	
	/*
	$contract_details_row = getkeyvalue2("eshop_info","contract_details","contract_id = '$contract_id' and seq = '$seq'","work_project,unit,unit_price,contracts_qty");
	$work_project = $contract_details_row['work_project'];
	$unit = $contract_details_row['unit'];
	$unit_price = $contract_details_row['unit_price'];
	$contracts_qty = $contract_details_row['contracts_qty'];
	$Qry = "insert into dispatch_contract_details (dispatch_id,contract_id,seq,work_project,unit,unit_price,contracts_qty) values ('$dispatch_id','$contract_id','$seq','$work_project','$unit','$unit_price','$contracts_qty')";
	*/
	
	$Qry = "insert into dispatch_contract_details (dispatch_id,contract_id,seq) values ('$dispatch_id','$contract_id','$seq')";

	$mDB->query($Qry);

	$mDB->remove();
	
    $objResponse->script("parent.dispatch_contract_details_myDraw();");
	$message01 = getlang("已新增!");
	$objResponse->script("jAlert('Success', '$message01', 'green', '', 1000);");
    //$objResponse->script("parent.$.fancybox.close();");
	
	
	return $objResponse;
}


$xajax->processRequest();

$fm = $_GET['fm'];
$dispatch_id = $_GET['dispatch_id'];
$contract_id = $_GET['contract_id'];
$show_title = getlang("合約工作項目選單");
$Close = getlang("關閉");

$dataTable_de = getDataTable_de();


$closebtn = "<button class=\"btn btn-danger\" type=\"button\" onclick=\"parent.$.fancybox.close();\" style=\"float:right;margin: 0 5px 0 0;\"><i class=\"bi bi-power\"></i>&nbsp;關閉</button>";


$card_header_color = "#EFFFBF";


$list_view=<<<EOT
<div class="card card_full">
	<div class="card-header" style="background-color:$card_header_color;">
		$closebtn
		<div class="size14 weight float-start" style="margin: 5px 15px 0 0;">
			<div class="inline me-3">合約工作項目選單</div>
		</div>
	</div>
	<div id="full" class="card-body data-overlayscrollbars-initialize">
		<table class="table table-bordered border-dark w-100" id="choice_table" style="min-width:740px;">
			<thead class="table-light border-dark">
				<tr style="border-bottom: 1px solid #000;">
					<th scope="col" class="text-center text-nowrap" style="width:10%;">選取</th>
					<th scope="col" class="text-center text-nowrap" style="width:5%;">項次</th>
					<th scope="col" class="text-center" style="width:50%;">工作項目</th>
					<th scope="col" class="text-center" style="width:10%;">單位</th>
					<th scope="col" class="text-center" style="width:15%;">單價</th>
					<th scope="col" class="text-center" style="width:10%;">契約數量</th>
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
			"ajaxSource": "/smarty/templates/$site_db/$templates/sub_modal/project/func08/dispatch_ms/server_contract.php?site_db=$site_db&contract_id=$contract_id",
			"language": {
						"sUrl": "$dataTable_de"
					},
			"fnRowCallback": function( nRow, aData, iDisplayIndex ) {

			
			//選取
			var getbtn = "xajax_getchoice('$dispatch_id','$contract_id','"+aData[1]+"');";
			var m_ch = '<div class="text-center"><button type="button" class="btn btn-primary btn-sm p-0 px-2 m-0" onclick="'+getbtn+'">選取</button></div>';
			
			$('td:eq(0)', nRow).html( m_ch );
			
			/*
			$('td:eq(1)', nRow).html( '<div class="text-center size12 weight">'+aData[1]+'</div>');
			$('td:eq(2)', nRow).html( '<div class="text-start size12">'+aData[2]+'</div>');
			$('td:eq(3)', nRow).html( '<div class="text-center size12 blue01 weight">'+aData[5]+'</div>');
			*/

			var seq = "";
			if (aData[1] != null && aData[1] != "")
				seq = aData[1];

			$('td:eq(1)', nRow).html( '<div class="size12 text-center text-nowrap">'+seq+'</div>' );

			var work_project = "";
			if (aData[2] != null && aData[2] != "")
				work_project = aData[2];

			$('td:eq(2)', nRow).html( '<div class="size12 text-start">'+work_project+'</div>' );

			var unit = "";
			if (aData[3] != null && aData[3] != "")
				unit = aData[3];

			$('td:eq(3)', nRow).html( '<div class="size12 text-center">'+unit+'</div>' );

			var unit_price = number_format(aData[4]);
			$('td:eq(4)', nRow).html( '<div class="text-center size12 blue02 weight">'+unit_price+'</div>' );

			var contracts_qty = number_format(aData[5]);
			$('td:eq(5)', nRow).html( '<div class="text-center size12 red weight">'+contracts_qty+'</div>' );


			return nRow;
			}
					
		});
	
		/* Init the table */
		oTable = $('#choice_table').dataTable();
		
	} );

</script>
EOT;

?>