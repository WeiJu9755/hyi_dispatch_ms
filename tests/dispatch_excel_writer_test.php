<?php

// Small PHPExcel stand-ins verify mapping and seven-item pagination without
// relying on the web server's /website/os installation in local tests.
class PHPExcel_Cell_DataType
{
    public const TYPE_STRING = 's';
}

class PHPExcel_Worksheet_PageSetup
{
    public const PAPERSIZE_A4 = 9;
    public const ORIENTATION_PORTRAIT = 'portrait';

    public $printArea;
    public $paperSize;
    public $orientation;
    public $fitToPage;
    public $fitToWidth;
    public $fitToHeight;
    public $horizontalCentered;
    public function setPrintArea($value) { $this->printArea = $value; }
    public function setPaperSize($value) { $this->paperSize = $value; }
    public function setOrientation($value) { $this->orientation = $value; }
    public function setFitToPage($value) { $this->fitToPage = $value; }
    public function setFitToWidth($value) { $this->fitToWidth = $value; }
    public function setFitToHeight($value) { $this->fitToHeight = $value; }
    public function setHorizontalCentered($value) { $this->horizontalCentered = $value; }
}

class TestPageMargins
{
    public $left;
    public $right;
    public $top;
    public $bottom;
    public function setLeft($value) { $this->left = $value; }
    public function setRight($value) { $this->right = $value; }
    public function setTop($value) { $this->top = $value; }
    public function setBottom($value) { $this->bottom = $value; }
}

class TestSheet
{
    public $title = '';
    public $cells = ['A18' => '營造業專業工作特定施工項目', 'A28' => '本日施工項目記錄'];
    public $columns = [];
    public $styles = [];
    public $rows = [];
    public $pageSetup;
    public $pageMargins;

    public function __clone()
    {
        $this->pageSetup = $this->pageSetup ? clone $this->pageSetup : null;
        $this->pageMargins = $this->pageMargins ? clone $this->pageMargins : null;
        foreach ($this->styles as $key => $style) {
            $this->styles[$key] = clone $style;
        }
        foreach ($this->rows as $key => $row) {
            $this->rows[$key] = clone $row;
        }
    }
    public function setTitle($title) { $this->title = $title; }
    public function getPageSetup()
    {
        if (!$this->pageSetup) {
            $this->pageSetup = new PHPExcel_Worksheet_PageSetup();
        }
        return $this->pageSetup;
    }
    public function getPageMargins()
    {
        if (!$this->pageMargins) {
            $this->pageMargins = new TestPageMargins();
        }
        return $this->pageMargins;
    }
    public function getColumnDimension($column)
    {
        if (!isset($this->columns[$column])) {
            $this->columns[$column] = new TestColumnDimension();
        }
        return $this->columns[$column];
    }
    public function getStyle($address)
    {
        if (!isset($this->styles[$address])) {
            $this->styles[$address] = new TestStyle();
        }
        return $this->styles[$address];
    }
    public function getRowDimension($row)
    {
        if (!isset($this->rows[$row])) {
            $this->rows[$row] = new TestRowDimension();
        }
        return $this->rows[$row];
    }
    public function getCell($address) { return new TestCell($this->cells[$address] ?? ''); }
    public function setCellValue($address, $value) { $this->cells[$address] = $value; }
    public function setCellValueExplicit($address, $value, $type) { $this->cells[$address] = $value; }
}

class TestCell
{
    private $value;
    public function __construct($value) { $this->value = $value; }
    public function getValue() { return $this->value; }
}

class TestStyle
{
    public $alignment;
    public function __construct() { $this->alignment = new TestAlignment(); }
    public function __clone() { $this->alignment = clone $this->alignment; }
    public function getAlignment() { return $this->alignment; }
}

class TestAlignment
{
    public $wrapText;
    public function setWrapText($value) { $this->wrapText = $value; }
}

class TestRowDimension
{
    public $height = -1;
    public function getRowHeight() { return $this->height; }
    public function setRowHeight($value) { $this->height = $value; }
}

class TestColumnDimension
{
    public $width;
    public function setWidth($width) { $this->width = $width; }
}

class TestWorkbook
{
    public $sheets;

    public function __construct() { $this->sheets = [new TestSheet()]; }
    public function getActiveSheet() { return $this->sheets[0]; }
    public function addSheet($sheet)
    {
        foreach ($this->sheets as $existing) {
            assert($existing->title !== $sheet->title);
        }
        $this->sheets[] = $sheet;
    }
    public function setActiveSheetIndex($index) {}
    public function disconnectWorksheets() {}
}

class TestReader
{
    public function setReadDataOnly($value) {}
    public function load($template) { return new TestWorkbook(); }
}

class TestWriter
{
    private $workbook;

    public function __construct($workbook) { $this->workbook = $workbook; }
    public function save($path) { file_put_contents($path, serialize($this->workbook->sheets)); }
}

class PHPExcel_IOFactory
{
    public static function createReader($format)
    {
        assert($format === 'Excel2007');
        return new TestReader();
    }

    public static function createWriter($workbook, $format)
    {
        assert($format === 'Excel5');
        return new TestWriter($workbook);
    }
}

require_once __DIR__ . '/../dispatch_ms/dispatch_excel_writer.php';

$dispatch = [
    'dispatch_id' => 'D20260924001',
    'dispatch_date' => '2026-09-24',
    'contract_id' => 'C100',
    'contract_caption' => '測試工程',
    'start_date' => '2026-09-01',
    'end_date' => '2026-09-30',
    'work_days' => 29,
    'total_contract_amount' => 1234567,
    'employee_count' => 6,
    'safety_employee_count' => 1,
];
$details = [];
for ($i = 1; $i <= 15; $i++) {
    $details[] = [
        'seq' => (string)$i,
        'work_project' => '項目' . $i,
        'unit' => '式',
        'contracts_qty' => 10,
        'actual_qty' => $i,
        'cumulative_qty' => $i * ($i + 1) / 2,
        'remark' => '備註' . $i,
    ];
}
$details[0]['work_project'] = str_repeat('長', 40);
$details[0]['remark'] = str_repeat('備', 30);

foreach ([0 => 1, 7 => 1, 9 => 2, 15 => 3] as $count => $expectedPages) {
    $output = tempnam(sys_get_temp_dir(), 'dispatch_excel_test_');
    try {
        writeDispatchExcel('template.xlsx', $output, $dispatch, array_slice($details, 0, $count));
        $sheets = unserialize(file_get_contents($output));
        assert(count($sheets) === $expectedPages);
        foreach ($sheets as $page => $sheet) {
            assert($sheet->title === '施工日誌' . ($page + 1));
            assert($sheet->columns === []);
            assert($sheet->pageSetup->printArea === 'A1:J42');
            assert($sheet->pageSetup->paperSize === PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
            assert($sheet->pageSetup->orientation === PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT);
            assert($sheet->pageSetup->fitToPage === true);
            assert($sheet->pageSetup->fitToWidth === 1);
            assert($sheet->pageSetup->fitToHeight === 1);
            assert($sheet->pageSetup->horizontalCentered === true);
            assert($sheet->pageMargins->left === 0.3);
            assert($sheet->pageMargins->right === 0.3);
            assert($sheet->pageMargins->top === 0.4);
            assert($sheet->pageMargins->bottom === 0.4);
            assert($sheet->cells['B1'] === 'C100');
            assert($sheet->cells['B2'] === 'D20260924001');
            assert($sheet->cells['B5'] === 29);
            assert($sheet->cells['I7'] === '原契約：1,234,567');
            assert($sheet->cells['C26'] === 6);
            assert($sheet->cells['C27'] === 1);
            assert($sheet->cells['A18'] === '營造業專業工作特定施工項目');
            assert($sheet->styles['B4']->alignment->wrapText === true);
            assert($sheet->styles['A11']->alignment->wrapText === true);
            assert($sheet->styles['I11']->alignment->wrapText === true);
            assert($sheet->styles['A28']->alignment->wrapText === true);
            if ($count > 0 && $page === 0) {
                assert($sheet->rows[11]->height > 17.5);
            }
            for ($row = 0; $row < 7; $row++) {
                $item = $details[$page * 7 + $row] ?? null;
                $expected = $page * 7 + $row < $count ? ($page * 7 + $row + 1) . ' ' . $item['work_project'] : '';
                assert($sheet->cells['A' . (11 + $row)] === $expected);
                $expectedCumulative = $page * 7 + $row < $count ? (float)$item['cumulative_qty'] : '';
                assert($sheet->cells['G' . (11 + $row)] === $expectedCumulative);
            }
        }
        if ($expectedPages > 1) {
            assert($sheets[1]->rows[11]->height < $sheets[0]->rows[11]->height);
        }
    } finally {
        unlink($output);
    }
}
echo "OK\n";
