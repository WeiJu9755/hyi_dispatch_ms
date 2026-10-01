<?php

/** Use the same PHPExcel 1.8.1 and Excel5 output as the existing supplier report. */
function writeDispatchExcel(string $template, string $output, array $dispatch, array $details): void
{
    if (!class_exists('PHPExcel_IOFactory', false)) {
        require_once '/website/os/PHPExcel-1.8.1/Classes/PHPExcel.php';
    }

    $reader = PHPExcel_IOFactory::createReader('Excel2007');
    $reader->setReadDataOnly(false);
    $workbook = $reader->load($template);
    $baseSheet = $workbook->getActiveSheet();
    $templateSheet = clone $baseSheet;
    $pages = array_chunk($details, 7);
    if (!$pages) {
        $pages = [[]];
    }

    foreach ($pages as $index => $pageDetails) {
        if ($index === 0) {
            $sheet = $baseSheet;
            $sheet->setTitle('施工日誌1');
        } else {
            $sheet = clone $templateSheet;
            $sheet->setTitle('施工日誌' . ($index + 1));
            $workbook->addSheet($sheet);
        }
        fillDispatchSheet($sheet, $dispatch, $pageDetails);
    }

    $workbook->setActiveSheetIndex(0);
    $writer = PHPExcel_IOFactory::createWriter($workbook, 'Excel5');
    $writer->save($output);
    $workbook->disconnectWorksheets();
}

function fillDispatchSheet($sheet, array $dispatch, array $details): void
{
    // Preserve the template's column widths and print each log on A4 portrait.
    $pageSetup = $sheet->getPageSetup();
    $pageSetup->setPrintArea('A1:J42');
    $pageSetup->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
    $pageSetup->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT);
    $pageSetup->setFitToPage(true);
    $pageSetup->setFitToWidth(1);
    $pageSetup->setFitToHeight(1);
    $pageSetup->setHorizontalCentered(true);
    $margins = $sheet->getPageMargins();
    $margins->setLeft(0.3);
    $margins->setRight(0.3);
    $margins->setTop(0.4);
    $margins->setBottom(0.4);

    $sheet->setCellValueExplicit('B1', (string)$dispatch['contract_id'], PHPExcel_Cell_DataType::TYPE_STRING);
    $sheet->setCellValueExplicit('B2', (string)$dispatch['dispatch_id'], PHPExcel_Cell_DataType::TYPE_STRING);
    $sheet->setCellValue('H2', (string)$dispatch['dispatch_date']);
    $sheet->setCellValue('B4', (string)$dispatch['contract_caption']);
    $sheet->setCellValue('B5', $dispatch['work_days'] === null ? '' : (int)$dispatch['work_days']);
    $sheet->setCellValue('J5', (string)$dispatch['start_date']);
    $sheet->setCellValue('J6', (string)$dispatch['end_date']);
    $sheet->setCellValue('I7', '原契約：' . number_format((float)$dispatch['total_contract_amount']));
    $sheet->setCellValue('C26', (int)$dispatch['employee_count']);
    $sheet->setCellValue('C27', (int)$dispatch['safety_employee_count']);

    foreach (['B1', 'B2', 'B4', 'I7'] as $address) {
        $sheet->getStyle($address)->getAlignment()->setWrapText(true);
    }
    dispatchSetRowHeight($sheet, 1, (string)$dispatch['contract_id'], 17);
    dispatchSetRowHeight($sheet, 2, (string)$dispatch['dispatch_id'], 7);
    dispatchSetRowHeight($sheet, 4, (string)$dispatch['contract_caption'], 42);
    dispatchSetRowHeight($sheet, 7, '原契約：' . number_format((float)$dispatch['total_contract_amount']), 21);

    // Excel does not reliably auto-size merged rows, so allow enough height
    // for the fixed notes and the variable item/remark text.
    foreach ([9, 20, 24, 28, 29, 30, 31, 32, 33, 34, 37, 38, 39, 40, 41, 42] as $row) {
        $sheet->getStyle('A' . $row)->getAlignment()->setWrapText(true);
        dispatchSetRowHeight($sheet, $row, (string)$sheet->getCell('A' . $row)->getValue(), 90);
    }

    // Replace all seven template formulas. Empty rows stay blank, and the
    // template's external workbook references are not carried into the report.
    for ($index = 0; $index < 7; $index++) {
        $row = 11 + $index;
        $item = $details[$index] ?? null;
        $sheet->setCellValue('A' . $row, $item ? trim($item['seq'] . ' ' . $item['work_project']) : '');
        $sheet->setCellValue('D' . $row, $item ? (string)($item['unit'] ?? '') : '');
        $sheet->setCellValue('E' . $row, $item && $item['contracts_qty'] !== null ? (float)$item['contracts_qty'] : '');
        $sheet->setCellValue('F' . $row, $item && $item['actual_qty'] !== null ? (float)$item['actual_qty'] : '');
        $sheet->setCellValue('G' . $row, $item && $item['cumulative_qty'] !== null ? (float)$item['cumulative_qty'] : '');
        $sheet->setCellValue('I' . $row, $item ? (string)($item['remark'] ?? '') : '');
        $sheet->getStyle('A' . $row)->getAlignment()->setWrapText(true);
        $sheet->getStyle('I' . $row)->getAlignment()->setWrapText(true);
        $name = $item ? trim($item['seq'] . ' ' . $item['work_project']) : '';
        $remark = $item ? (string)($item['remark'] ?? '') : '';
        dispatchSetRowHeight($sheet, $row, $name, 29);
        dispatchSetRowHeight($sheet, $row, $remark, 20);
    }
}

/** Estimate rows for CJK text; merged cells are not auto-sized by Excel. */
function dispatchSetRowHeight($sheet, int $row, string $text, int $lineWidth): void
{
    if ($text === '') {
        return;
    }
    $lines = 0;
    foreach (preg_split('/\R/u', $text) as $part) {
        preg_match_all('/./us', $part, $matches);
        $units = 0;
        foreach ($matches[0] as $character) {
            $units += strlen($character) > 1 ? 2 : 1;
        }
        $lines += max(1, (int)ceil($units / $lineWidth));
    }
    $dimension = $sheet->getRowDimension($row);
    $current = $dimension->getRowHeight();
    $dimension->setRowHeight(max($current > 0 ? $current : 17.5, $lines * 15 + 2));
}
