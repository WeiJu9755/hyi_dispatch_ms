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
	<table class="table table-bordered border-dark w-100" id="dispatch_attendance_sub_table" style="min-width:650px;">
		<thead class="table-light border-dark">
			<tr style="border-bottom: 1px solid #000;">
				<th scope="col" class="text-center text-nowrap" style="width:10%;">姓名</th>
				<th scope="col" class="text-center text-nowrap" style="width:12%;">工號</th>
				<th scope="col" class="text-center text-nowrap" style="width:8%;">開始時間</th>
				<th scope="col" class="text-center text-nowrap" style="width:8%;">迄止時間</th>
				<th scope="col" class="text-center text-nowrap" style="width:6%;">工時</th>
				<th scope="col" class="text-center text-nowrap" style="width:40%;">備註</th>
				<th scope="col" class="text-center text-nowrap" style="width:8%;">是否加班</th>
				<th scope="col" class="text-center text-nowrap" style="width:8%;">處理</th>
			</tr>
		</thead>
		<tbody class="table-group-divider">
			<tr>
				<td colspan="8" class="dataTables_empty">資料載入中...</td>
			</tr>
		</tbody>
		<tfoot class="table-light border-dark">
			<tr style="border-bottom: 1px solid #000;">
				<th colspan="4" class="text-end">合計</th>
				<th scope="col" class="text-end"></th>
				<th colspan="3"></th>
			</tr>
		</tfoot>
	</table>
</div>
EOT;



$scroll = true;
if (!($detect->isMobile() && !$detect->isTablet())) {
	$scroll = false;
}


$show_dispatch_attendance_sub=<<<EOT
<style>
#dispatch_attendance_sub_table {
	width: 100% !Important;
	margin: 5px 0 0 0 !Important;
}
</style>

$list_view

<script>
	var oTable;
	$(document).ready(function() {
		$('#dispatch_attendance_sub_table').dataTable( {
			"processing": false,
			"serverSide": true,
			"responsive":  {
				details: true
			},//RWD響應式
			"scrollX": '$scroll',
			"paging": false,
			"searching": false,  //禁用原生搜索
			"ordering": false,
			"ajaxSource": "/smarty/templates/$site_db/$templates/sub_modal/project/func08/dispatch_ms/server_dispatch_attendance_sub.php?site_db=$site_db&web_id=$web_id&dispatch_id=$dispatch_id&contract_id=$contract_id&seq=$seq",
			"info": false,
			"language": {
						"sUrl": "$dataTable_de"
					},
			"fnRowCallback": function( nRow, aData, iDisplayIndex ) { 

				//姓名
				$('td:eq(0)', nRow).html( '<div class="text-center size14 weight blue02">'+aData[10]+'</div>' );
				//工號
				$('td:eq(1)', nRow).html( '<div class="text-center size14 weight text-nowrap">'+aData[9]+'</div>' );

				//開始時間
				var attendance_start = "";
				if (aData[0] != null && aData[0] != "" && aData[0] != "00:00:00")
					attendance_start = '<span class="text-centersize14 weight text-nowrap">'+aData[0].substr(0, 5)+'</span>';

				$('td:eq(2)', nRow).html( '<div class="text-center size14">'+attendance_start+'</div>' );

				//迄止時間
				var attendance_end = "";
				if (aData[1] != null && aData[1] != "" && aData[1] != "00:00:00")
					attendance_end = '<span class="text-centersize14 weight text-nowrap">'+aData[1].substr(0, 5)+'</span>';

				$('td:eq(3)', nRow).html( '<div class="text-center size14">'+attendance_end+'</div>' );

				//工時
				var attendance_hours = "";
				if (aData[2] != null && aData[2] != "")
					attendance_hours = aData[2];

				$('td:eq(4)', nRow).html( '<div class="text-center size14">'+attendance_hours+'</div>' );

				//備註
				var attendance_remark = "";
				if (aData[3] != null && aData[3] != "")
					attendance_remark = aData[3];

				$('td:eq(5)', nRow).html( '<div class="text-start size14">'+attendance_remark+'</div>' );

				//是否加班
				if ( aData[4] == "Y" ) {
					var mcheck = "xajax_is_overtime("+aData[5]+",'N');";
					var img_check = '<a href="javascript:void(0);" onclick="'+mcheck+'"><i class="bi bi-check-circle size16 green weight"></i></a>';
				} else {
					var mcheck = "xajax_is_overtime("+aData[5]+",'Y');";
					var img_check = '<a href="javascript:void(0);" onclick="'+mcheck+'"><i class="bi bi-circle size16 gray"></i></a>';
				}
				$('td:eq(6)', nRow).html( '<div class="text-center">'+img_check+'</div>' );

				var url1 = "openfancybox_edit('/index.php?ch=dispatch_attendance_sub_modify&auto_seq="+aData[5]+"&fm=$fm',700,600,'');";
				var mdel = "dispatch_attendance_sub_myDel("+aData[5]+");";

				var show_btn = '';
				show_btn = '<div class="btn-group text-nowrap">'
						+'<button type="button" class="btn btn-light" onclick="'+url1+'" title="修改"><i class="bi bi-pencil-square"></i></button>'
						+'<button type="button" class="btn btn-light" onclick="'+mdel+'" title="刪除"><i class="bi bi-trash"></i></button>'
						+'</div>';

				$('td:eq(7)', nRow).html( '<div class="text-center">'+show_btn+'</div>' );
			

				return nRow;
			},
			"footerCallback": function( row, data, start, end, display ) {
				var api = this.api();
				
				// 1. 定義合計函數，使用純數字避免 NaN
				var sumColumn = function(i) {
					return api
						.column(i)
						.data()
						.reduce(function (a, b) {
							// 移除非數字的值並進行加總
							var x = parseFloat(a) || 0;
							var y = parseFloat(b) || 0;
							return x + y;
						}, 0);
				};

				// 2. 合計指定欄位，例如第 3 欄（從 0 開始計算）
				var attendance_hours_total = sumColumn(2);

				// 3. 將合計結果插入到 footer 中
				//$(api.column(2).footer()).html(total.toFixed(2)); // 保留兩位小數
				$( api.column( 4 ).footer() ).html( '<div class="text-center size14 blue02 weight text-nowrap">'+attendance_hours_total+'</div>' );
				

				
			}
		});
	
		/* Init the table */
		oTable = $('#dispatch_attendance_sub_table').dataTable();
		
	} );
	
var dispatch_attendance_sub_myDel = function(auto_seq){
	xajax_dispatch_attendance_sub_DeleteRow(auto_seq);
	return true;
};

var dispatch_attendance_sub_myDraw = function(){
	var oTable;
	oTable = $('#dispatch_attendance_sub_table').dataTable();
	oTable.fnDraw(false);
}

</script>

EOT;

?>