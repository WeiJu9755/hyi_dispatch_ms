<?php


session_start();
$memberID = $_SESSION['memberID'];
$powerkey = $_SESSION['powerkey'];


//載入公用函數
@include_once '/website/include/pub_function.php';

@include_once("/website/class/".$site_db."_info_class.php");

// 使用xajax
@include_once '/website/xajax/xajax_core/xajax.inc.php';
$xajax = new xajax();

$xajax->registerFunction("processform");
function processform($aFormValues){

	$objResponse = new xajaxResponse();
	

	
	$auto_seq		= trim($aFormValues['auto_seq']);
	$memberID		= trim($aFormValues['memberID']);
	$actual_qty		= ceil(trim($aFormValues['actual_qty']));

	//存入實體資料庫中
	$mDB = "";
	$mDB = new MywebDB();
	
	$Qry = "UPDATE dispatch_contract_details set
			`actual_qty` = '$actual_qty'
			 ,last_modify = now()
			 ,makeby	= '$memberID'
			where auto_seq = '$auto_seq'";
	$mDB->query($Qry);
	
	$mDB->remove();
	
	$objResponse->script("updispatch();");
	$objResponse->script("myDraw();");
	$objResponse->script("art.dialog.tips('已存檔!',1);");
	$objResponse->script("parent.$.fancybox.close();");
	
	return $objResponse;
}

$xajax->processRequest();


$auto_seq = $_GET['auto_seq'];


$mDB = "";
$mDB = new MywebDB();

$Qry="SELECT * FROM dispatch_contract_details
WHERE auto_seq = '$auto_seq'";
$mDB->query($Qry);
$total = $mDB->rowCount();
if ($total > 0) {
    //已找到符合資料
	$row=$mDB->fetchRow(2);
	$dispatch_id = $row['dispatch_id'];
	$actual_qty = $row['actual_qty'];

}

$mDB->remove();




$show_center=<<<EOT
<style type="text/css">

.card_full {
	width:100%;
	height:100vh;
}

#full {
	width: 100%;
	height: 100%;
}

#info_container {
	width: 100% !Important;
	margin: 10px auto !Important;
}

</style>
<div class="card card_full">
	<div id="full" class="card-body data-overlayscrollbars-initialize">
		<div id="info_container">
			<form method="post" id="modifyForm" name="modifyForm" enctype="multipart/form-data" action="javascript:void(null);">
			<div style="width:auto;margin: 0;padding:0;">
				<div class="field_container3 px-5 size14" style="margin-bottom: 30px;">
					<div>
						<div class="pb-1 weight">實際(工/次/台/只)數:</div> 
						<div>
							<input type="text" class="inputtext w-100" id="actual_qty" name="actual_qty" value="$actual_qty" style="max-width:150px;"/>
						</div> 
					</div>
				</div>
				<div class="form_btn_div">
					<input type="hidden" name="auto_seq" value="$auto_seq" />
					<input type="hidden" name="memberID" value="$memberID" />
					<button id="save" class="btn btn-primary" type="button" onclick="CheckValue(this.form);" style="padding: 10px;margin-right: 10px;"><i class="bi bi-check-lg green"></i>&nbsp;&nbsp;存檔</button>
					<!--
					<button class="btn btn-warning" type="button" onclick="clearall();" style="padding: 10px;margin-right: 10px;"><i class="bi bi-x-lg"></i>&nbsp;&nbsp;清除</button>
					-->
					<button class="btn btn-danger" type="button" onclick="parent.$.fancybox.close();" style="padding: 10px;"><i class="bi bi-power"></i>&nbsp;&nbsp;關閉</button>
				</div>
			</div>
			</form>
		</div>
	</div>
</div>
<script type="text/javascript">

function CheckValue(thisform) {
	xajax_processform(xajax.getFormValues('modifyForm'));
	thisform.submit();
}

function clearall() {
	$("#actual_qty").val("");
}


var myDraw = function(){
	var oTable;
	oTable = parent.$('#dispatch_contract_details_table').dataTable();
	oTable.fnDraw(false);
}

var updispatch = function(){

	var site_db = '$site_db';
	var templates = '$templates';
	var dispatch_id = '$dispatch_id';

	var url = '/smarty/templates/'+site_db+'/'+templates+'/sub_modal/project/func08/dispatch_ms/ajax_update_dispatch.php'; 

	$.ajax({
		url: url, 
		type: 'GET',
		data: { dispatch_id: dispatch_id },
		dataType: 'text', 
		success: function(data) {
		},
		error: function() {
		}
	});

}

$(document).ready(async function() {
	//等待其他資源載入完成，此方式適用大部份瀏覽器
	await new Promise(resolve => setTimeout(resolve, 100));
	$('#actual_qty').focus();
	$('#actual_qty').select();
});


// 取得表單和預設按鈕
const form = document.getElementById('modifyForm');
const defaultButton = document.getElementById('save');

// 監聽表單的提交事件
form.addEventListener('submit', function(event) {
    event.preventDefault(); // 防止表單的默認提交行為

    // 執行按鈕的預設操作
    defaultButton.click();
});

// 或者，監聽整個窗口的「Enter」鍵按下事件
window.addEventListener('keydown', function(event) {
    // 確認是否按下了「Enter」鍵
    if (event.key === 'Enter') {
        event.preventDefault(); // 防止表單的默認提交行為

        // 執行按鈕的預設操作
        defaultButton.click();
    }
});


</script>

EOT;

?>