<?php

header('Content-Type: application/json; charset=utf-8');

$site_db = $_POST['site_db'];
$material_no = $_POST['material_no'];

//載入公用函數
//@include_once '/website/include/pub_function.php';

@include_once("/website/class/".$site_db."_info_class.php");

$mDB = "";
$mDB = new MywebDB();

//先檢查是否已在在
$Qry="select material_name,specification from inventory where material_no = '$material_no'";
$mDB->query($Qry);
$caption = "";
if ($mDB->rowCount() > 0) {
    //已找到符合資料
	$row=$mDB->fetchRow(2);
	$material_name = $row['material_name'];
	$specification = $row['specification'];
}

$warehouse_list = array();

//取得倉庫別
$Qry="SELECT * FROM inventory_sub
WHERE material_no = '$material_no'
ORDER BY auto_seq";
$mDB->query($Qry);
if ($mDB->rowCount() > 0) {
	while ($row=$mDB->fetchRow(2)) {
		$warehouse = $row['warehouse'];
		$warehouse_list[] = $warehouse;
	}
}

$mDB->remove();


$return_val=array(
	"success"=>true
	,"material_name"=>$material_name
	,"specification"=>$specification
	,"warehouse_list"=>$warehouse_list
);

echo json_encode($return_val, JSON_UNESCAPED_UNICODE);

?>