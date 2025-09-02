<?php

session_start();

$memberID = $_SESSION['memberID'];
$powerkey = $_SESSION['powerkey'];


//載入公用函數
@include_once '/website/include/pub_function.php';

@include_once("/website/class/".$site_db."_info_class.php");


$m_location		= "/website/smarty/templates/".$site_db."/".$templates;
$m_pub_modal	= "/website/smarty/templates/".$site_db."/pub_modal";

$sid = "";
if (isset($_GET['sid']))
	$sid = $_GET['sid'];


//程式分類
$ch = empty($_GET['ch']) ? 'default' : $_GET['ch'];
switch($ch) {
	case 'add':
		$title = "新增資料";
		$sid = "view01";
		$modal = $m_location."/sub_modal/project/func08/dispatch_ms/dispatch_add.php";
		include $modal;
		$smarty->assign('show_center',$show_center);
		$smarty->assign('xajax_javascript', $xajax->getJavascript('/xajax/'));
		break;
	case 'edit':
		$title = "派工編輯作業";
		$sid = "view01";
		$modal = $m_location."/sub_modal/project/func08/dispatch_ms/dispatch_modify.php";
		include $modal;
		$smarty->assign('show_center',$show_center);
		$smarty->assign('xajax_javascript', $xajax->getJavascript('/xajax/'));
		break;
	case 'mview':
	case 'view':
		$title = "資料瀏覽";
		if (empty($sid))
			$sid = "mbpjitem";
		$modal = $m_location."/sub_modal/project/func08/dispatch_ms/dispatch_view.php";
		include $modal;
		//$smarty->assign('show_center',$show_center);
		$smarty->assign('xajax_javascript', $xajax->getJavascript('/xajax/'));
		break;
		/*
	case 'dispatch_day_summary':
		if (empty($sid))
			$sid = "view01";
		$modal = $m_location."/sub_modal/project/func08/dispatch_ms/dispatch_day_summary.php";
		include $modal;
		$smarty->assign('show_center',$show_center);
		break;
	case 'excel':
		$title = "匯出Excel".$mt;
		if (empty($sid))
			$sid = "view01";
		$modal = $m_location."/sub_modal/project/func08/dispatch_ms/dispatch_report_excel.php";
		include $modal;
		$smarty->assign('show_center',$show_center);
		break;
	case 'attendance_end':
		$title = "收工狀況";
		if (empty($sid))
			$sid = "view01";
		$modal = $m_location."/sub_modal/project/func08/dispatch_ms/attendance_end_modify.php";
		include $modal;
		$smarty->assign('show_center',$show_center);
		$smarty->assign('xajax_javascript', $xajax->getJavascript('/xajax/'));
		break;
	case 'leave':
		$title = "請休假";
		if (empty($sid))
			$sid = "view01";
		$modal = $m_location."/sub_modal/project/func08/dispatch_ms/leave_modify.php";
		include $modal;
		$smarty->assign('show_center',$show_center);
		$smarty->assign('xajax_javascript', $xajax->getJavascript('/xajax/'));
		break;
	case 'work_overtime':
		$title = "加班";
		if (empty($sid))
			$sid = "view01";
		$modal = $m_location."/sub_modal/project/func08/dispatch_ms/work_overtime_modify.php";
		include $modal;
		$smarty->assign('show_center',$show_center);
		$smarty->assign('xajax_javascript', $xajax->getJavascript('/xajax/'));
		break;
	case 'knock_off':
		$title = "收工作業";
		$sid = "view01";
		$modal = $m_location."/sub_modal/project/func08/dispatch_ms/dispatch_knock_off.php";
		include $modal;
		$smarty->assign('show_center',$show_center);
		$smarty->assign('xajax_javascript', $xajax->getJavascript('/xajax/'));
		break;
	case 'knock_off_edit':
		$title = "收工修改";
		$sid = "view01";
		$modal = $m_location."/sub_modal/project/func08/dispatch_ms/dispatch_knock_off_modify.php";
		include $modal;
		$smarty->assign('show_center',$show_center);
		$smarty->assign('xajax_javascript', $xajax->getJavascript('/xajax/'));
		break;
	case 'attendance_manpower':
		$title = "修改人力";
		$sid = "view01";
		$modal = $m_location."/sub_modal/project/func08/dispatch_ms/attendance_manpower_modify.php";
		include $modal;
		$smarty->assign('show_center',$show_center);
		$smarty->assign('xajax_javascript', $xajax->getJavascript('/xajax/'));
		break;
	case 'attendance_day':
		$title = "修改工時";
		$sid = "view01";
		$modal = $m_location."/sub_modal/project/func08/dispatch_ms/attendance_day_modify.php";
		include $modal;
		$smarty->assign('show_center',$show_center);
		$smarty->assign('xajax_javascript', $xajax->getJavascript('/xajax/'));
		break;
	case 'workinghours':
		$title = "修改總工時";
		$sid = "view01";
		$modal = $m_location."/sub_modal/project/func08/dispatch_ms/workinghours_modify.php";
		include $modal;
		$smarty->assign('show_center',$show_center);
		$smarty->assign('xajax_javascript', $xajax->getJavascript('/xajax/'));
		break;
		*/
	case 'ch_contract':
		$title = "合約工作項目選單";
		if (empty($sid))
			$sid = "view01";
		$modal = $m_location."/sub_modal/project/func08/dispatch_ms/ch_contract.php";
		include $modal;
		$smarty->assign('show_center',$show_center);
		$smarty->assign('xajax_javascript', $xajax->getJavascript('/xajax/'));
		break;
	case 'actual_qty':
		$title = "實際(工/次/台/只)數";
		$sid = "view01";
		$modal = $m_location."/sub_modal/project/func08/dispatch_ms/actual_qty_modify.php";
		include $modal;
		$smarty->assign('show_center',$show_center);
		$smarty->assign('xajax_javascript', $xajax->getJavascript('/xajax/'));
		break;
	case 'dispatch_attendance':
		$title = "人員派工";
		if (empty($sid))
			$sid = "view01";
		$modal = $m_location."/sub_modal/project/func08/dispatch_ms/dispatch_attendance.php";
		include $modal;
		$smarty->assign('show_center',$show_center);
		$smarty->assign('xajax_javascript', $xajax->getJavascript('/xajax/'));
		break;
	case 'dispatch_attendance_sub_add':
		$title = "新增派工人員";
		if (empty($sid))
			$sid = "view01";
		$modal = $m_location."/sub_modal/project/func08/dispatch_ms/dispatch_attendance_sub_add.php";
		include $modal;
		$smarty->assign('show_center',$show_center);
		$smarty->assign('xajax_javascript', $xajax->getJavascript('/xajax/'));
		break;
	case 'dispatch_attendance_sub_modify':
		$title = "編輯派工人員";
		if (empty($sid))
			$sid = "view01";
		$modal = $m_location."/sub_modal/project/func08/dispatch_ms/dispatch_attendance_sub_modify.php";
		include $modal;
		$smarty->assign('show_center',$show_center);
		$smarty->assign('xajax_javascript', $xajax->getJavascript('/xajax/'));
		break;
	case 'ch_employee':
		$title = "員工名單";
		if (empty($sid))
			$sid = "view01";
		$modal = $m_location."/sub_modal/project/func08/dispatch_ms/ch_employee.php";
		include $modal;
		$smarty->assign('show_center',$show_center);
		$smarty->assign('xajax_javascript', $xajax->getJavascript('/xajax/'));
		break;
	case 'ch_team':
		$title = "團隊選單";
		if (empty($sid))
			$sid = "view01";
		$modal = $m_location."/sub_modal/project/func08/dispatch_ms/ch_team.php";
		include $modal;
		$smarty->assign('show_center',$show_center);
		$smarty->assign('xajax_javascript', $xajax->getJavascript('/xajax/'));
		break;
	case 'ch_team_member':
		$title = "團員選單";
		if (empty($sid))
			$sid = "view01";
		$modal = $m_location."/sub_modal/project/func08/dispatch_ms/ch_team_member.php";
		include $modal;
		$smarty->assign('show_center',$show_center);
		$smarty->assign('xajax_javascript', $xajax->getJavascript('/xajax/'));
		break;
	case 'dispatch_material_details_add':
		$title = "新增物料名稱/使用機具";
		if (empty($sid))
			$sid = "view01";
		$modal = $m_location."/sub_modal/project/func08/dispatch_ms/dispatch_material_details_add.php";
		include $modal;
		$smarty->assign('show_center',$show_center);
		$smarty->assign('xajax_javascript', $xajax->getJavascript('/xajax/'));
		break;
	case 'dispatch_material_details_modify':
		$title = "編輯資料";
		if (empty($sid))
			$sid = "view01";
		$modal = $m_location."/sub_modal/project/func08/dispatch_ms/dispatch_material_details_modify.php";
		include $modal;
		$smarty->assign('show_center',$show_center);
		$smarty->assign('xajax_javascript', $xajax->getJavascript('/xajax/'));
		break;
	default:
		if (empty($sid))
			$sid = "mbpjitem";
		$modal = $m_location."/sub_modal/project/func08/dispatch_ms/dispatch.php";
		include $modal;
		$smarty->assign('xajax_javascript', $xajax->getJavascript('/xajax/'));
		break;
};

?>