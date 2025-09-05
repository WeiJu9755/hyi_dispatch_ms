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
	

	if (trim($aFormValues['material_no']) == "")	{
		$objResponse->script("jAlert('警示', '請輸入物料編碼', 'red', '', 2000);");
		return $objResponse;
		exit;
	}
	if (trim($aFormValues['contract_detail_seq']) == "")	{
		$objResponse->script("jAlert('警示', '請選擇合約項次', 'red', '', 2000);");
		return $objResponse;
		exit;
	}
	if (trim($aFormValues['warehouse']) == "")	{
		$objResponse->script("jAlert('警示', '請選擇倉庫別', 'red', '', 2000);");
		return $objResponse;
		exit;
	}

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
		$dispatch_id		= trim($aFormValues['dispatch_id']);
		$contract_id		= trim($aFormValues['contract_id']);
		$remarks			= trim($aFormValues['remarks']);
		$memberID			= trim($aFormValues['memberID']);

		// 合約項次分解
		 $contract_detail_seq_raw = trim($aFormValues['contract_detail_seq']);

		// 用 | 拆成陣列
		list($seq, $work_project) = explode('|', $contract_detail_seq_raw, 2);

		// 安全處理（避免 SQL injection）
		$seq = addslashes($seq);
		$work_project = addslashes($work_project);
		
	
		//存入實體資料庫中
		$mDB = "";
		$mDB = new MywebDB();

		//檢查物料編碼是否重覆
		$Qry="SELECT material_no FROM dispatch_material_details WHERE dispatch_id = '$dispatch_id' AND material_no = '$material_no'";
		$mDB->query($Qry);
		$total = $mDB->rowCount();
		if ($total > 0) {
			$mDB->remove();
			$objResponse->script("jAlert('警示', '您輸入的物料編碼已重複，請重新輸入新的', 'red', '', 2000);");
			return $objResponse;
			exit;
		}
	  
		$Qry="INSERT INTO dispatch_material_details (dispatch_id,seq,work_project,contract_id,material_no,warehouse,stock_out_qty,remarks,last_modify) values ('$dispatch_id','$seq','$work_project','$contract_id','$material_no','$warehouse','$stock_out_qty','$remarks',now())";
		$mDB->query($Qry);

        $mDB->remove();

		$objResponse->script("parent.dispatch_material_details_myDraw();");
		$objResponse->script("parent.$.fancybox.close();");
		
	};
	
	return $objResponse;	
}

$xajax->processRequest();

$fm = $_GET['fm'];
$dispatch_id = $_GET['dispatch_id'];
$contract_id = $_GET['contract_id'];

$mess_title = $title;



$mDB = "";
$mDB = new MywebDB();


//載入所有料件編號
$Qry="select material_no,material_name,specification from inventory order by material_no";
$mDB->query($Qry);
$material_no_list = "";

if ($mDB->rowCount() > 0) {
	while ($row=$mDB->fetchRow(2)) {
		$ch_material_no = $row['material_no'];
		$ch_material_name = $row['material_name'];
		$ch_specification = $row['specification'];
		$material_no_list .= "<option value=\"$ch_material_no\">$ch_material_no $ch_material_name $ch_specification</option>";
	}
}


//載入倉庫別
$Qry="SELECT caption FROM items where pro_id ='warehouse' ORDER BY pro_id,orderby";
$mDB->query($Qry);
$select_warehouse = "";
$select_warehouse .= "<option></option>";

if ($mDB->rowCount() > 0) {
	while ($row=$mDB->fetchRow(2)) {
		$ch_warehouse = $row['caption'];
		$select_warehouse .= "<option value=\"$ch_warehouse\" ".mySelect($ch_warehouse,$warehouse).">$ch_warehouse</option>";
	}
}

//載入合約別
$Qry="SELECT contract_id,seq,work_project 
FROM contract_details
WHERE contract_id = '$contract_id'";
$mDB->query($Qry);
$select_contract_detail_seq = "";
$select_contract_detail_seq .= "<option></option>";

if ($mDB->rowCount() > 0) {
	while ($row=$mDB->fetchRow(2)) {
		$seq = $row['seq'];
		$work_project = $row['work_project'];
		$select_contract_detail_seq .= "<option value=\"{$seq}|{$work_project}\">{$seq} {$work_project}</option>";
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
						<div class="field_div2">
							<input list="material_no_list" type="text" class="inputtext w-100" id="material_no" name="material_no" autocomplete="off" style="width:100%;max-width:250px;"/>
							<datalist id="material_no_list">
								$material_no_list
							</datalist>
						</div> 
					</div>
					<div>
						<div class="field_div1">合約項次:</div> 
						<div class="field_div2">
							<select id="contract_detail_seq" name="contract_detail_seq" placeholder="合約項次" style="width:100%;max-width:250px;" onchange="setEdit();">
								$select_contract_detail_seq
							</select>
						</div> 
					</div>
					<div>
						<div class="field_div1"></div> 
						<div class="field_div2">
							<div id="material_info"></div>
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
							<input type="text" class="inputtext w-100" id="stock_out_qty" name="stock_out_qty" style="width:100%;max-width:180px;"/>
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
					<input type="hidden" name="dispatch_id" value="$dispatch_id" />
					<input type="hidden" name="contract_id" value="$contract_id" />
					<input type="hidden" name="memberID" value="$memberID" />
					<button class="btn btn-primary" type="button" onclick="CheckValue(this.form);" style="padding: 10px;margin-right: 10px;"><i class="bi bi-check-lg green"></i>&nbsp;確定新增</button>
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


  $('#material_no').on('input', function() {
    var material_no = $(this).val();  // 即時取得 input 的值
    //$('#material_info').text(material_no);   // 顯示在畫面上
	if (material_no !== '') {
		$.ajax({
			url: '$ajax_get_inventory', // 後端 PHP 檔案
			method: 'POST',
			data: { site_db : '$site_db', material_no: material_no },
			dataType: 'json',
			success: function (response) {
				if (response.success) {
					$('#material_info').text(response.material_name+' '+response.specification);

					var warehouse_select = $('#warehouse');
      				warehouse_select.empty(); // 清空原本的選單

					var warehouse_list = response.warehouse_list;
					
					$.each(response.warehouse_list, function(index, warehouse) {
        				warehouse_select.append($('<option></option>').val(warehouse).text(warehouse));
      				});

				} else {
					$('#material_info').text('');
				}

			},
			error: function () {
		    	$('#material_info').text('');   // 顯示在畫面上
			}
		});

	} else {
    	$('#material_info').text('');   // 顯示在畫面上
	}

  });



</script>
EOT;


?>