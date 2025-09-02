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
	<table class="table table-bordered border-dark w-100" id="dispatch_contract_details_table" style="min-width:1400px;">
		<thead class="table-light border-dark">
			<tr style="border-bottom: 1px solid #000;">
				<th scope="col" class="text-center text-nowrap" style="width:3%;">序</th>
				<th scope="col" class="text-center text-nowrap" style="width:29%;">合約項次</th>
				<th scope="col" class="text-center text-nowrap" style="width:3%;">單位</th>
				<th scope="col" class="text-center text-nowrap" style="width:5%;">單價</th>
				<th scope="col" class="text-center text-nowrap" style="width:5%;">契約數量</th>
				<th scope="col" class="text-center text-nowrap" style="width:7%;">實際(工/次/台/只)數</th>
				<th scope="col" class="text-center text-nowrap" style="width:5%;">複價</th>
				<th scope="col" class="text-center text-nowrap" style="width:43%;">人員派工</th>
			</tr>
		</thead>
		<tbody class="table-group-divider">
			<tr>
				<td colspan="8" class="dataTables_empty">資料載入中...</td>
			</tr>
		</tbody>
		<tfoot class="table-light border-dark">
			<tr style="border-bottom: 1px solid #000;">
				<th scope="col" colspan="6" class="text-end size14 weight">合計</th>
				<th scope="col" class="text-center text-nowrap"></th>
				<th scope="col"></th>
			</tr>
		</tfoot>
	</table>
</div>
EOT;



$scroll = true;
if (!($detect->isMobile() && !$detect->isTablet())) {
	$scroll = false;
}


$show_dispatch_contract_details=<<<EOT
<style>
#dispatch_contract_details_table {
	width: 100% !Important;
	margin: 5px 0 0 0 !Important;
}
</style>

$list_view

<script>
	var oTable;
	$(document).ready(function() {
		$('#dispatch_contract_details_table').dataTable( {
			"processing": false,
			"serverSide": true,
			"responsive":  {
				details: true
			},//RWD響應式
			"scrollX": '$scroll',
			"paging": false,
			"searching": false,  //禁用原生搜索
			"ordering": false,
			"ajaxSource": "/smarty/templates/$site_db/$templates/sub_modal/project/func08/dispatch_ms/server_dispatch_contract_details.php?site_db=$site_db&dispatch_id=$dispatch_id&contract_id=$contract_id",
			"info": false,
			"language": {
						"sUrl": "$dataTable_de"
					},
			"fnRowCallback": function( nRow, aData, iDisplayIndex ) { 


				//顯示自動流水序號
				var seq_no = "";
				seq_no = iDisplayIndex + 1;
				$('td:eq(0)', nRow).html( '<div class="d-flex justify-content-center align-items-center size14 weight text-center" style="height:auto;min-height:32px;">('+seq_no+')</div>' );


				//合約項次
				var seq = "";
				if (aData[0] != null && aData[0] != "")
					seq = '<div class="inline size14 blue02 weight me-2">'+aData[0]+'</div>';

				var work_project = "";
				if (aData[1] != null && aData[1] != "")
					work_project = '<div class="inline size14 weight">'+aData[1]+'</div>';

				$('td:eq(1)', nRow).html( '<div class="d-flex align-items-center" style="height:auto;min-height:32px;">'+seq+work_project+'</div>' );

				//單位
				var unit = "";
				if (aData[2] != null && aData[2] != "")
					unit = aData[2];

				$('td:eq(2)', nRow).html( '<div class="d-flex justify-content-center align-items-center size14 text-center" style="height:auto;min-height:32px;">'+unit+'</div>' );

				//單價
				var unit_price = "";
				if (aData[3] != null && aData[3] != "")
					unit_price = number_format(aData[3]);

				$('td:eq(3)', nRow).html( '<div class="d-flex justify-content-center align-items-center size14 text-center text-nowrap" style="height:auto;min-height:32px;">'+unit_price+'</div>' );

				//契約數量
				var contracts_qty = "";
				if (aData[4] != null && aData[4] != "")
					contracts_qty = number_format(aData[4]);

				$('td:eq(4)', nRow).html( '<div class="d-flex justify-content-center align-items-center size14 text-center" style="height:auto;min-height:32px;">'+contracts_qty+'</div>' );

				//實際(工/次/台/只)數
				var actual_qty = '<span class="size14 weight red text-nowrap">'+number_format(aData[5])+'</span>';

				$('td:eq(5)', nRow).html( '<div class="d-flex justify-content-center align-items-center text-center" style="height:auto;min-height:32px;">'+actual_qty+'</div>' );

				//複價
				var subtotal = "";
				subtotal = number_format(aData[3]*aData[5]);

				$('td:eq(6)', nRow).html( '<div class="d-flex justify-content-center align-items-center size14 text-center text-nowrap" style="height:auto;min-height:32px;">'+subtotal+'</div>' );

				//人員派工
				var attendance_list = '<div id="attendance_list'+aData[7]+'"></div>';
				xajax_returnValue(aData[7],aData[8],aData[9],aData[0]);

				/*
				var dispatch_attendance_url = "openfancybox_edit('/index.php?ch=dispatch_attendance&auto_seq="+aData[7]+"&dispatch_id=$dispatch_id&fm=$fm',1200,'96%','');";
				if ('$disabled' == "disabled") {
					var show_dispatch_attendance_btn = '<button disabled type="button" class="btn btn-light btn-sm me-2" onclick="'+dispatch_attendance_url+'" title="人員派工"><i class="bi bi-person-raised-hand"></i></button>';
				} else {
					var show_dispatch_attendance_btn = '<button type="button" class="btn btn-light btn-sm me-2" onclick="'+dispatch_attendance_url+'" title="人員派工"><i class="bi bi-person-raised-hand"></i></button>';
				}
				*/

				$('td:eq(7)', nRow).html( '<div class="mytable w-100"><div class="myrow"><div class="mycell" style="width:95%;">'+attendance_list+'</div></div></div>' );


				return nRow;
			},
			"footerCallback": function( row, data, start, end, display ) {
				var api = this.api();

				// Helper: 將字串轉成數字
				var parseNumber = function (value) {
				return typeof value === 'string'
					? parseFloat(value.replace(/[^0-9.-]+/g, '')) || 0
					: typeof value === 'number'
					? value
					: 0;
				};				
				
				// 總計變數
				var price_total = 0;

				// 逐列計算欄位3 * 欄位5
				api.rows({ page: 'current' }).every(function () {
					var data = this.data();
					var price = parseNumber(data[3]);  // 欄位3：單價
					var qty = parseNumber(data[5]); // 欄位5：數量
					price_total += price * qty;
				});


				// 更新 footer 的總計
				$( api.column( 6 ).footer() ).html( '<div class="text-center size14 weight text-nowrap">'+number_format(price_total)+'</div>' );

				
			}
		});
	
		/* Init the table */
		oTable = $('#dispatch_contract_details_table').dataTable();
		
	} );
	
</script>

EOT;

?>