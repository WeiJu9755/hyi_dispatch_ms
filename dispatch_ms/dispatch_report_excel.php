<?php

if (empty($_SESSION['memberID']) || !isset($_GET['auto_seq']) || !ctype_digit((string)$_GET['auto_seq'])) {
    http_response_code(400);
    exit('工單資料不正確');
}

$auto_seq = (int)$_GET['auto_seq'];
$mDB = new MywebDB();
$Qry = "SELECT
    a.dispatch_id,
    a.dispatch_date,
    a.contract_id,
    b.contract_caption,
    b.start_date,
    b.end_date,
    DATEDIFF(b.end_date, b.start_date) AS work_days,
    COALESCE(c.total_contract_amount, 0) AS total_contract_amount,
    COALESCE(d.employee_count, 0) AS employee_count,
    COALESCE(d.safety_employee_count, 0) AS safety_employee_count
FROM dispatch a
LEFT JOIN contract b ON b.contract_id = a.contract_id
LEFT JOIN (
    SELECT das.dispatch_id,
        COUNT(DISTINCT das.employee_id) AS employee_count,
        COUNT(DISTINCT CASE WHEN e.employee_type = '工安' THEN das.employee_id END) AS safety_employee_count
    FROM dispatch_attendance_sub das
    LEFT JOIN employee e ON e.employee_id = das.employee_id
    GROUP BY das.dispatch_id
) d ON d.dispatch_id = a.dispatch_id
LEFT JOIN (
    SELECT contract_id, SUM(unit_price * contracts_qty) AS total_contract_amount
    FROM contract_details
    GROUP BY contract_id
) c ON c.contract_id = a.contract_id
WHERE a.auto_seq = $auto_seq";
$mDB->query($Qry);
if ($mDB->rowCount() < 1) {
    $mDB->remove();
    http_response_code(404);
    exit('找不到工單');
}
$dispatch = $mDB->fetchRow(2);
$dispatch_id = $dispatch['dispatch_id'];

// The ID comes from the selected dispatch row, not from a request parameter.
$safe_dispatch_id = str_replace("'", "''", $dispatch_id);
$Qry = "SELECT a.dispatch_id, a.contract_id, a.seq,
    b.work_project, b.unit, b.contracts_qty,
    a.actual_qty, a.remark,
    COALESCE((
        SELECT SUM(history_item.actual_qty)
        FROM dispatch_contract_details history_item
        INNER JOIN dispatch history_dispatch
            ON history_dispatch.dispatch_id = history_item.dispatch_id
        WHERE history_item.contract_id = a.contract_id
            AND history_item.seq = a.seq
            AND (
                history_dispatch.dispatch_date < current_dispatch.dispatch_date
                OR (history_dispatch.dispatch_date = current_dispatch.dispatch_date
                    AND history_dispatch.auto_seq <= current_dispatch.auto_seq)
            )
    ), 0) AS cumulative_qty
FROM dispatch_contract_details a
INNER JOIN dispatch current_dispatch ON current_dispatch.dispatch_id = a.dispatch_id
LEFT JOIN contract_details b ON b.contract_id = a.contract_id AND b.seq = a.seq
WHERE a.dispatch_id = '$safe_dispatch_id'
ORDER BY a.seq, a.auto_seq";
$mDB->query($Qry);
$details = [];
while ($row = $mDB->fetchRow(2)) {
    $details[] = $row;
}
$mDB->remove();

require_once __DIR__ . '/dispatch_excel_writer.php';
$template = __DIR__ . '/施工日誌20260924.xlsx';
$output = tempnam(sys_get_temp_dir(), 'dispatch_excel_');
if ($output === false) {
    http_response_code(500);
    exit('無法建立暫存檔案');
}

try {
    writeDispatchExcel($template, $output, $dispatch, $details);
    $filename = '施工日誌_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $dispatch_id) . '.xls';
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
    header('Content-Length: ' . filesize($output));
    header('Cache-Control: private, no-store');
    readfile($output);
} catch (Throwable $exception) {
    http_response_code(500);
    exit('施工日誌匯出失敗：' . $exception->getMessage());
} finally {
    @unlink($output);
}
exit;
