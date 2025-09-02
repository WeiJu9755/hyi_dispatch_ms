<?php


//error_reporting(E_ALL); 
//ini_set('display_errors', '1');


require_once '/website/os/Mobile-Detect-2.8.34/Mobile_Detect.php';
$detect = new Mobile_Detect;

if( $detect->isMobile() && !$detect->isTablet() ){
	$isMobile = 1;
} else {
	$isMobile = 0;
}


$fm = $_GET['fm'];


$sure_to_delete = getlang("您確定要刪除此筆資料嗎?");

$dataTable_de = getDataTable_de();
$Prompt = getlang("提示訊息");
$Confirm = getlang("確認");
$Cancel = getlang("取消");


$list_view=<<<EOT
<div class="w-100">
	<table class="table table-bordered border-dark w-100" id="dispatch_material_details_table" style="min-width:1400px;">
		<thead class="table-light border-dark">
			<tr style="border-bottom: 1px solid #000;">
				<th scope="col" class="text-center text-nowrap" style="width:3%;">序</th>
				<th scope="col" class="text-center text-nowrap" style="width:10%;">料號</th>
				<th scope="col" class="text-center text-nowrap" style="width:30%;">物料名稱/使用機具</th>
				<th scope="col" class="text-center text-nowrap" style="width:5%;">單位</th>
				<th scope="col" class="text-center text-nowrap" style="width:10%;">倉庫別</th>
				<th scope="col" class="text-center text-nowrap" style="width:10%;">數量</th>
				<th scope="col" class="text-center text-nowrap" style="width:30%;">備註</th>
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


$show_dispatch_material_details=<<<EOT
<style>
#dispatch_material_details_table {
	width: 100% !Important;
	margin: 5px 0 0 0 !Important;
}
</style>

$list_view

<script>
	var oTable;
	$(document).ready(function() {
		$('#dispatch_material_details_table').dataTable( {
			"processing": false,
			"serverSide": true,
			"responsive":  {
				details: true
			},//RWD響應式
			"scrollX": '$scroll',
			"paging": false,
			"searching": false,  //禁用原生搜索
			"ordering": false,
			"ajaxSource": "/smarty/templates/$site_db/$templates/sub_modal/project/func08/dispatch_ms/server_dispatch_material_details.php?site_db=$site_db&dispatch_id=$dispatch_id&contract_id=$contract_id",
			"info": false,
			"language": {
						"sUrl": "$dataTable_de"
					},
			"fnRowCallback": function( nRow, aData, iDisplayIndex ) { 

				//顯示自動流水序號
				var seq = "";
				seq = iDisplayIndex + 1;
				$('td:eq(0)', nRow).html( '<div class="text-center size14 weight">('+seq+')</div>' );

				var material_no = "";
				if (aData[1] != null && aData[1] != "")
					material_no = aData[1];

				$('td:eq(1)', nRow).html( '<div class="text-center size14 blue02 weight">'+material_no+'</div>' );

				var material_name = "";
				if (aData[2] != null && aData[2] != "")
					material_name = aData[2];

				$('td:eq(2)', nRow).html( '<div class="text-start size14 weight">'+material_name+'</div>' );

				var unit = "";
				if (aData[3] != null && aData[3] != "")
					unit = aData[3];

				$('td:eq(3)', nRow).html( '<div class="text-center size14">'+unit+'</div>' );

				var warehouse = "";
				if (aData[4] != null && aData[4] != "")
					warehouse = aData[4];

				$('td:eq(4)', nRow).html( '<div class="text-center size14">'+warehouse+'</div>' );

				var stock_out_qty = "";
				if (aData[5] != null && aData[5] != 0)
					stock_out_qty = number_format(aData[5]);

				$('td:eq(5)', nRow).html( '<div class="text-center size14 red weight">'+stock_out_qty+'</div>' );

				//備註
				var remarks = "";
				if (aData[6] != null && aData[6] != "")
					remarks = aData[6];

				$('td:eq(6)', nRow).html( '<div class="text-start size14">'+remarks+'</div>' );

				return nRow;
			}
		});
	
		/* Init the table */
		oTable = $('#dispatch_material_details_table').dataTable();
		
	} );
	
</script>

EOT;

?>