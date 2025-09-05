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
<div class="w-100 p-3">
	<table class="table table-bordered border-dark w-100" id="dispatch_contract_details_table" style="min-width:1720px;">
		<thead class="table-light border-dark">
			<tr style="border-bottom: 1px solid #000;">
				<th scope="col" class="text-center text-nowrap" style="width:3%;">序</th>
				<th scope="col" class="text-center text-nowrap" style="width:26%;">合約項次</th>
				<th scope="col" class="text-center text-nowrap" style="width:3%;">單位</th>
				<th scope="col" class="text-center text-nowrap" style="width:5%;">單價</th>
				<th scope="col" class="text-center text-nowrap" style="width:5%;">契約數量</th>
				<th scope="col" class="text-center text-nowrap" style="width:7%;">實際(工/次/台/只)數</th>
				<th scope="col" class="text-center text-nowrap" style="width:5%;">複價</th>
				<th scope="col" class="text-center text-nowrap" style="width:23%;">人員派工</th>
				<th scope="col" class="text-center text-nowrap" style="width:20%;">備註</th>
				<th scope="col" class="text-center text-nowrap" style="width:3%;">移除</th>
			</tr>
		</thead>
		<tbody class="table-group-divider">
			<tr>
				<td colspan="9" class="dataTables_empty">資料載入中...</td>
			</tr>
		</tbody>
		<tfoot class="table-light border-dark">
			<tr style="border-bottom: 1px solid #000;">
				<th scope="col" colspan="6" class="text-end size14 weight">合計</th>
				<th scope="col" class="text-center text-nowrap"></th>
				<th scope="col" colspan="3"></th>
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


				/*
				var auto_seq = "";
				if (aData[7] != null && aData[7] != "")
					auto_seq = aData[7];

				$('td:eq(0)', nRow).html( '<div class="text-center size14 weight">'+auto_seq+'</div>' );
				*/

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
				var actual_qty_url = "openfancybox_edit('/index.php?ch=actual_qty&auto_seq="+aData[7]+"&fm=$fm',300,220,'');";
				if ('$disabled' == "disabled") {
					var actual_qty = '<span class="size14 weight red text-nowrap">'+number_format(aData[5])+'</span>';
				} else {
					var actual_qty = '<button type="button" class="btn btn-light btn-sm px-2 size14 weight red text-nowrap" onclick="'+actual_qty_url+'" title="修改實際(工/次/台/只)數">'+number_format(aData[5])+'</button>';
				}

				$('td:eq(5)', nRow).html( '<div class="d-flex justify-content-center align-items-center text-center" style="height:auto;min-height:32px;">'+actual_qty+'</div>' );

				//複價
				var subtotal = "";
				subtotal = number_format(aData[3]*aData[5]);

				$('td:eq(6)', nRow).html( '<div class="d-flex justify-content-center align-items-center size14 text-center text-nowrap" style="height:auto;min-height:32px;">'+subtotal+'</div>' );

				//人員派工
				var attendance_list = '<div id="attendance_list'+aData[7]+'"></div>';
				xajax_returnValue(aData[7],aData[8],aData[9],aData[0]);

				var dispatch_attendance_url = "openfancybox_edit('/index.php?ch=dispatch_attendance&auto_seq="+aData[7]+"&dispatch_id=$dispatch_id&fm=$fm',1200,'96%','');";
				if ('$disabled' == "disabled") {
					var show_dispatch_attendance_btn = '<button disabled type="button" class="btn btn-light btn-sm me-2" onclick="'+dispatch_attendance_url+'" title="人員派工"><i class="bi bi-person-raised-hand"></i></button>';
				} else {
					var show_dispatch_attendance_btn = '<button type="button" class="btn btn-light btn-sm me-2" onclick="'+dispatch_attendance_url+'" title="人員派工"><i class="bi bi-person-raised-hand"></i></button>';
				}

				$('td:eq(7)', nRow).html( '<div class="mytable w-100"><div class="myrow"><div class="mycell" style="width:20px;">'+show_dispatch_attendance_btn+'</div><div class="mycell" style="width:95%;">'+attendance_list+'</div></div></div>' );

				//備註
				
				var remark_url = "openfancybox_edit('/index.php?ch=remark&auto_seq="+aData[7]+"&fm=$fm',500,220,'');";
				if (aData[6] == null || aData[6] == "") {
					if ('$disabled' == "disabled") {
						var remark = '<span class="size14 weight text-nowrap" style="color:#777777ff;"></span>';
					} else {
						var remark = '<button type="button" class="btn btn-light btn-sm px-2 size14 weight text-nowrap" style="color:#777777ff" onclick="'+remark_url+'" title="備註">請輸入工項對應任務內容</button>';
					}
				} else {
					if ('$disabled' == "disabled") {
						var remark = '<span class="size14 weight text-nowrap" style="color:#777777ff;">' + aData[6] + '</span>';
					} else {
						var remark = '<button type="button" class="btn btn-light btn-sm px-2 size14 weight text-nowrap" style="color:#777777ff" onclick="'+remark_url+'" title="備註">' + aData[6] + '</button>';
					}
				}

				//移除
				$('td:eq(8)', nRow).html( '<div class="d-flex justify-content-center align-items-center text-center" style="height:auto;min-height:32px;">'+remark+'</div>' );

				if ('$disabled' == "disabled") {
					$('td:eq(8)', nRow).html( '' );
				} else {
					var mdel = "dispatch_contract_details_myDel('"+aData[7]+"','"+aData[8]+"','"+aData[9]+"','"+aData[0]+"');";
					var mdel_btn = '<div class="inline" style="margin: 0 7px 0 7px;"><a href="javascript:void(0);" onclick="'+mdel+'" title="移除"><i class="bi bi-x-lg size12"></i></a></div>';
					
					$('td:eq(9)', nRow).html( '<div class="text-center">'+mdel_btn+'</div>' );
				}
				

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
				
				/*
				var price_total = api.column(6, { page: 'current' }).data().reduce( function (a, b) {
					return Number(a) + Number(b);
				}, 0 );
				*/

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

				/*
				var workinghours_total = api.column(5, { page: 'current' }).data().reduce( function (a, b) {
					return Number(a) + Number(b);
				}, 0 );

				// 更新 footer 的總計
				$( api.column( 6 ).footer() ).html( '<div class="text-center size14 weight text-nowrap">'+workinghours_total+'</div>' );
				*/
				
			}
		});
	
		/* Init the table */
		oTable = $('#dispatch_contract_details_table').dataTable();
		
	} );
	
var dispatch_contract_details_myDel = function(auto_seq,dispatch_id,contract_id,seq){
	/*
	xajax_Contract_DetailsDeleteRow(auto_seq,dispatch_id,contract_id,seq);
	return true;
	*/

	Swal.fire({
	title: "您確定要刪除此筆資料嗎?",
	text: "此項作業會刪除所有與此筆記錄有關的資料",
	icon: "question",
	showCancelButton: true,
	confirmButtonColor: "#3085d6",
	cancelButtonColor: "#d33",
	cancelButtonText: "取消",
	confirmButtonText: "刪除"
	}).then((result) => {
		if (result.isConfirmed) {
			xajax_Contract_DetailsDeleteRow(auto_seq,dispatch_id,contract_id,seq);
		}
	});


};

var dispatch_contract_details_myDraw = function(){
	var oTable;
	oTable = $('#dispatch_contract_details_table').dataTable();
	oTable.fnDraw(false);
}

</script>

EOT;

?>