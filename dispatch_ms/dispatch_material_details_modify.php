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
	
	
	$bError = false;

	$material_no		= trim($aFormValues['material_no']);
	$warehouse			= trim($aFormValues['warehouse']);
	$stock_out_qty		= trim($aFormValues['stock_out_qty']);
	

	if ((int)trim($aFormValues['stock_out_qty']) < 1)	{
		$objResponse->script("jAlert('警示', '數量不可小於1', 'red', '', 2000);");
		return $objResponse;
		exit;
	}

	//取得此倉庫的庫存量
	$stock_qty = 0;

	$mDB = "";
	$mDB = new MywebDB();

	$Qry="SELECT stock_qty FROM inventory_sub WHERE material_no = '$material_no' AND warehouse = '$warehouse'";
	$mDB->query($Qry);
	if ($mDB->rowCount() > 0) {
		//已找到符合資料
		$row=$mDB->fetchRow(2);
		$stock_qty = $row['stock_qty'];
	}
    $mDB->remove();
	
	if ($stock_out_qty > $stock_qty)	{
		$objResponse->script("jAlert('警示', '數量:{$stock_out_qty}已超過倉庫庫存量:{$stock_qty}', 'red', '', 2000);");
		return $objResponse;
		exit;
	}


	if (!$bError) {
		$fm					= trim($aFormValues['fm']);
		$site_db			= trim($aFormValues['site_db']);
		$templates			= trim($aFormValues['templates']);
		$web_id				= trim($aFormValues['web_id']);
		$auto_seq			= trim($aFormValues['auto_seq']);
		$dispatch_id		= trim($aFormValues['dispatch_id']);
		$remarks			= trim($aFormValues['remarks']);
		$memberID			= trim($aFormValues['memberID']);
		

		
		//存入實體資料庫中
		$mDB = "";
		$mDB = new MywebDB();

		$Qry="UPDATE dispatch_material_details set
				 warehouse			= '$warehouse'
				,stock_out_qty		= '$stock_out_qty'
				,remarks			= '$remarks'
				,last_modify		= now()
				where auto_seq = '$auto_seq'";

		$mDB->query($Qry);

        $mDB->remove();

		$objResponse->script("parent.dispatch_material_details_myDraw();");
		$objResponse->script("parent.$.fancybox.close();");
		
	};
	
	return $objResponse;	
}

$xajax->processRequest();

$fm = $_GET['fm'];
$auto_seq = $_GET['auto_seq'];

$mess_title = $title;



$mDB = "";
$mDB = new MywebDB();

$Qry="SELECT a.*,b.material_name,b.specification,b.unit FROM dispatch_material_details a
LEFT JOIN inventory b ON b.material_no = a.material_no
WHERE a.auto_seq = '$auto_seq'";
$mDB->query($Qry);
$total = $mDB->rowCount();
if ($total > 0) {
    //已找到符合資料
	$row=$mDB->fetchRow(2);
	$dispatch_id = $row['dispatch_id'];
	$contract_id = $row['contract_id'];
	$material_no = $row['material_no'];
	$material_name = $row['material_name'];
	$specification = $row['specification'];
	$unit = $row['unit'];
	$warehouse = $row['warehouse'];
	$stock_out_qty = $row['stock_out_qty'];
	$remarks = $row['remarks'];
	$last_modify = $row['last_modify'];
  
}

//取得倉庫別
$Qry="SELECT * FROM inventory_sub
WHERE material_no = '$material_no'
ORDER BY auto_seq";
$mDB->query($Qry);

$select_warehouse = "";
$select_warehouse .= "<option></option>";

if ($mDB->rowCount() > 0) {
	while ($row=$mDB->fetchRow(2)) {
		$ch_warehouse = $row['warehouse'];
		$select_warehouse .= "<option value=\"$ch_warehouse\" ".mySelect($ch_warehouse,$warehouse).">$ch_warehouse</option>";
	}
}

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
	width: 800px !Important;
	margin: 0 auto !Important;
}

.field_div1 {width:150px;display: none;font-size:18px;color:#000;text-align:right;font-weight:700;padding:15px 10px 0 0;vertical-align: top;display:inline-block;zoom: 1;*display: inline;}
.field_div2 {width:100%;max-width:630px;display: none;font-size:18px;color:#000;text-align:left;font-weight:700;padding:8px 0 0 0;vertical-align: top;display:inline-block;zoom: 1;*display: inline;}

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


$m_location		= "/smarty/templates/".$site_db."/".$templates;
$ajax_get_inventory = $m_location."/sub_modal/project/func08/dispatch_ms/ajax_get_inventory.php";


$show_center=<<<EOT
<script src="/os/Autogrow-Textarea/jquery.autogrowtextarea.min.js"></script>

$style_css
<div class="card card_full">
	<div class="card-header text-bg-info">
		<div class="size14 weight float-start me-3 mt-2">
			$mess_title
		</div>
	</div>
	<div id="full" class="card-body data-overlayscrollbars-initialize">
		<div id="info_container">
			<form method="post" id="addForm" name="addForm" enctype="multipart/form-data" action="javascript:void(null);">
				<div class="field_container3">
					<div>
						<div class="field_div1">物料編碼:</div> 
						<div class="field_div2"><div class="blue weight mt-2">$material_no</div></div>
					</div>
					<div>
						<div class="field_div1"></div> 
						<div class="field_div2">
							<div id="material_info">$material_name</div>
						</div> 
					</div>
					<div>
						<div class="field_div1">倉庫別:</div> 
						<div class="field_div2">
							<select id="warehouse" name="warehouse" placeholder="請選擇倉庫別" style="width:100%;max-width:250px;">
								$select_warehouse
							</select>
						</div> 
					</div>
					<div>
						<div class="field_div1">數量:</div> 
						<div class="field_div2">
							<input type="text" class="inputtext w-100" id="stock_out_qty" name="stock_out_qty" value="$stock_out_qty" style="width:100%;max-width:180px;"/>
						</div> 
					</div>
					<div>
						<div class="field_div1">備註:</div> 
						<div class="field_div2">
							<textarea class="inputtext w-100 p-3" id="remarks" name="remarks" cols="80" rows="2" style="max-width: 500px;" onchange="setEdit();">$remarks</textarea>
						</div> 
					</div>
				</div>
				<div class="form_btn_div mt-5">
					<input type="hidden" name="fm" value="$fm" />
					<input type="hidden" name="site_db" value="$site_db" />
					<input type="hidden" name="templates" value="$templates" />
					<input type="hidden" name="web_id" value="$web_id" />
					<input type="hidden" name="auto_seq" value="$auto_seq" />
					<input type="hidden" name="material_no" value="$material_no" />
					<input type="hidden" name="memberID" value="$memberID" />
					<button class="btn btn-primary" type="button" onclick="CheckValue(this.form);" style="padding: 10px;margin-right: 10px;"><i class="bi bi-check-lg green"></i>&nbsp;確定存檔</button>
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
	oTable = parent.$('#dispatch_material_details_table').dataTable();
	oTable.fnDraw(false);
}
	
$(document).ready(function() {
	$("#remarks").autoGrow({
		extraLine: true // Adds an extra line at the end of the textarea. Try both and see what works best for you.
	});
});

$(document).ready(async function() {
	//等待其他資源載入完成，此方式適用大部份瀏覽器
	await new Promise(resolve => setTimeout(resolve, 100));
	$('#material_no').focus();
});

</script>
EOT;


?>