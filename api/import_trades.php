<?php
require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

$userId = requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'POST required.');
}

if (!isset($_FILES['trade_file']) || $_FILES['trade_file']['error'] !== UPLOAD_ERR_OK) {
    $errCodes = [
        UPLOAD_ERR_INI_SIZE   => 'File exceeds server limit.',
        UPLOAD_ERR_FORM_SIZE  => 'File exceeds form limit.',
        UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
        UPLOAD_ERR_NO_FILE    => 'No file uploaded.',
    ];
    $code = $_FILES['trade_file']['error'] ?? UPLOAD_ERR_NO_FILE;
    jsonResponse(false, $errCodes[$code] ?? 'Upload failed.');
}

$file    = $_FILES['trade_file'];
$ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$tmpPath = $file['tmp_name'];
$maxSize = 5 * 1024 * 1024; // 5 MB

if ($file['size'] > $maxSize) {
    jsonResponse(false, 'File too large. Maximum allowed size is 5 MB.');
}

if (!in_array($ext, ['csv', 'xlsx'])) {
    jsonResponse(false, 'Unsupported file type. Please upload a .csv or .xlsx file.');
}

// ---- Parse file ----
$rows = ($ext === 'csv') ? parseCsvFile($tmpPath) : parseXlsxFile($tmpPath);

if ($rows === false || count($rows) === 0) {
    jsonResponse(false, 'File is empty or could not be read.');
}

// ---- Map header row ----
$rawHeader = array_shift($rows);
if (empty($rawHeader)) {
    jsonResponse(false, 'File has no header row.');
}

$header = array_map(function ($h) {
    return strtolower(trim(preg_replace('/[\s\-]+/', '_', $h)));
}, $rawHeader);

$colMap = mapColumns($header);

$required = ['asset_name', 'trade_type', 'entry_price', 'exit_price', 'trade_date'];
foreach ($required as $col) {
    if (!isset($colMap[$col])) {
        jsonResponse(false, "Required column not found: \"$col\". Please use the provided template.");
    }
}

// ---- Insert rows ----
$db   = getDB();
$stmt = $db->prepare('
    INSERT INTO trades (user_id, asset_name, trade_type, entry_price, exit_price, quantity, trade_date, notes, emotion)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
');

$imported = 0;
$skipped  = 0;
$errors   = [];

foreach ($rows as $i => $row) {
    $rowNum  = $i + 2; // row 1 is header

    // Skip entirely blank rows
    if (implode('', array_map('trim', $row)) === '') {
        $skipped++;
        continue;
    }

    // Extract values
    $asset   = trim($row[$colMap['asset_name']]   ?? '');
    $typeRaw = trim($row[$colMap['trade_type']]    ?? '');
    $entryRaw= trim($row[$colMap['entry_price']]   ?? '');
    $exitRaw = trim($row[$colMap['exit_price']]    ?? '');
    $dateRaw = trim($row[$colMap['trade_date']]    ?? '');
    $qty     = isset($colMap['quantity']) ? trim($row[$colMap['quantity']] ?? '') : '';
    $notes   = isset($colMap['notes'])   ? trim($row[$colMap['notes']]    ?? '') : '';
    $emotion = isset($colMap['emotion']) ? trim($row[$colMap['emotion']]  ?? '') : '';

    // Validate
    if ($asset === '') {
        $errors[] = "Row $rowNum: Asset name is required."; $skipped++; continue;
    }

    $type = ucfirst(strtolower($typeRaw));
    if (!in_array($type, ['Buy', 'Sell'])) {
        $errors[] = "Row $rowNum: Trade type must be Buy or Sell (got \"$typeRaw\")."; $skipped++; continue;
    }

    $entry = parseNumber($entryRaw);
    if ($entry === false || $entry <= 0) {
        $errors[] = "Row $rowNum: Invalid entry price \"$entryRaw\"."; $skipped++; continue;
    }

    $exit = parseNumber($exitRaw);
    if ($exit === false || $exit <= 0) {
        $errors[] = "Row $rowNum: Invalid exit price \"$exitRaw\"."; $skipped++; continue;
    }

    $date = parseDate($dateRaw);
    if (!$date) {
        $errors[] = "Row $rowNum: Cannot parse date \"$dateRaw\". Use YYYY-MM-DD or MM/DD/YYYY."; $skipped++; continue;
    }

    $qtyNum = ($qty !== '' && is_numeric($qty) && (float)$qty > 0) ? (float)$qty : 1.0;

    $validEmotions = ['Confident','Calm','Patient','Fearful','Greedy','Impulsive','Uncertain'];
    $emotionVal    = in_array($emotion, $validEmotions) ? $emotion : null;

    try {
        $stmt->execute([$userId, $asset, $type, $entry, $exit, $qtyNum, $date, $notes, $emotionVal]);
        $imported++;
    } catch (PDOException $e) {
        $errors[] = "Row $rowNum: Database error — " . $e->getMessage();
        $skipped++;
    }
}

$msg = "$imported trade(s) imported successfully.";
if ($skipped > 0) $msg .= " $skipped row(s) skipped.";

jsonResponse(true, $msg, [
    'imported' => $imported,
    'skipped'  => $skipped,
    'errors'   => array_slice($errors, 0, 25),
]);

// ================================================================
// HELPERS
// ================================================================

function parseCsvFile(string $path): array|false {
    $rows   = [];
    $handle = fopen($path, 'r');
    if ($handle === false) return false;

    // Strip UTF-8 BOM if present
    $bom = fread($handle, 3);
    if ($bom !== "\xEF\xBB\xBF") rewind($handle);

    while (($row = fgetcsv($handle, 0, ',')) !== false) {
        $rows[] = array_map('trim', $row);
    }
    fclose($handle);
    return $rows;
}

function parseXlsxFile(string $path): array|false {
    if (!class_exists('ZipArchive')) return false;

    $zip = new ZipArchive();
    if ($zip->open($path) !== true) return false;

    // ---- Shared strings ----
    $sharedStrings = [];
    $ssXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($ssXml) {
        // Remove default namespace so SimpleXML can parse without prefix issues
        $ssXml = preg_replace('/\sxmlns[^"]*"[^"]*"/', '', $ssXml, 1);
        $ss = @simplexml_load_string($ssXml);
        if ($ss) {
            foreach ($ss->si as $si) {
                $text = '';
                if (isset($si->t)) {
                    $text = (string)$si->t;
                } elseif (isset($si->r)) {
                    foreach ($si->r as $r) {
                        if (isset($r->t)) $text .= (string)$r->t;
                    }
                }
                $sharedStrings[] = $text;
            }
        }
    }

    // ---- First worksheet ----
    // Try sheet1 by name, then by index in workbook
    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    if (!$sheetXml) {
        // Try finding the first sheet path from workbook.xml
        $wbXml = $zip->getFromName('xl/workbook.xml');
        if ($wbXml) {
            preg_match('/r:id="(rId\d+)"/', $wbXml, $m);
            $rId = $m[1] ?? 'rId1';
            $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
            if ($relsXml && preg_match('/Id="' . preg_quote($rId) . '"[^>]+Target="([^"]+)"/', $relsXml, $rm)) {
                $sheetXml = $zip->getFromName('xl/' . $rm[1]);
            }
        }
    }
    $zip->close();

    if (!$sheetXml) return false;

    // Remove default namespace
    $sheetXml = preg_replace('/\sxmlns[^"]*"[^"]*"/', '', $sheetXml, 1);
    $sheet    = @simplexml_load_string($sheetXml);
    if (!$sheet || !isset($sheet->sheetData)) return false;

    $result = [];
    $maxCol = 0;
    $rowMap = [];

    foreach ($sheet->sheetData->row as $row) {
        $ri    = (int)$row['r'] - 1;
        $cells = [];

        foreach ($row->c as $cell) {
            $ref  = (string)$cell['r'];
            $ci   = xlsColIndex($ref);
            $type = (string)($cell['t'] ?? '');
            $v    = '';

            if ($type === 'inlineStr') {
                $v = isset($cell->is->t) ? (string)$cell->is->t : '';
            } elseif (isset($cell->v)) {
                $raw = (string)$cell->v;
                switch ($type) {
                    case 's':   $v = $sharedStrings[(int)$raw] ?? ''; break;
                    case 'str': $v = $raw; break;
                    case 'b':   $v = $raw === '1' ? 'TRUE' : 'FALSE'; break;
                    default:    $v = $raw; // number or date serial
                }
            }

            $cells[$ci] = $v;
            $maxCol = max($maxCol, $ci);
        }
        $rowMap[$ri] = $cells;
    }

    ksort($rowMap);
    foreach ($rowMap as $cells) {
        $filled = [];
        for ($ci = 0; $ci <= $maxCol; $ci++) {
            $filled[] = $cells[$ci] ?? '';
        }
        $result[] = $filled;
    }

    return $result;
}

function xlsColIndex(string $ref): int {
    preg_match('/^([A-Z]+)/i', $ref, $m);
    $col = strtoupper($m[1] ?? 'A');
    $idx = 0;
    for ($i = 0, $len = strlen($col); $i < $len; $i++) {
        $idx = $idx * 26 + (ord($col[$i]) - 64);
    }
    return $idx - 1;
}

function mapColumns(array $header): array {
    $aliases = [
        'asset_name'  => ['asset_name','asset','symbol','ticker','name','stock','pair','instrument','asset_name','market'],
        'trade_type'  => ['trade_type','type','direction','side','action','trade_direction','buy/sell','long/short'],
        'entry_price' => ['entry_price','entry','buy_price','open','open_price','entry_price','purchase_price'],
        'exit_price'  => ['exit_price','exit','sell_price','close','close_price','exit_price','sale_price'],
        'quantity'    => ['quantity','qty','shares','units','size','lots','lot_size','volume','contracts'],
        'trade_date'  => ['trade_date','date','trade_date','open_date','date_opened','datetime','trade_day'],
        'notes'       => ['notes','note','comment','comments','remarks','reflection','description','journal'],
        'emotion'     => ['emotion','mood','feeling','state','mental_state','psychology','mindset'],
    ];

    $map = [];
    foreach ($header as $idx => $col) {
        $col = str_replace([' ', '-'], '_', strtolower(trim($col)));
        foreach ($aliases as $field => $names) {
            if (in_array($col, $names) && !isset($map[$field])) {
                $map[$field] = $idx;
                break;
            }
        }
    }
    return $map;
}

function parseNumber(string $val): float|false {
    $val = trim(str_replace([',', '$', '€', '£', ' '], '', $val));
    if (!is_numeric($val)) return false;
    $f = (float)$val;
    return $f > 0 ? $f : false;
}

function parseDate(string $val): string|false {
    $val = trim($val);
    if ($val === '') return false;

    // Excel date serial number (e.g. 45292)
    if (is_numeric($val) && (int)$val > 1000) {
        $ts = ((int)$val - 25569) * 86400;
        return $ts > 0 ? date('Y-m-d', $ts) : false;
    }

    // Common string formats
    $formats = ['Y-m-d', 'm/d/Y', 'd/m/Y', 'Y/m/d', 'm-d-Y', 'd-m-Y', 'n/j/Y', 'j/n/Y'];
    foreach ($formats as $fmt) {
        $d = DateTime::createFromFormat($fmt, $val);
        if ($d !== false) {
            return $d->format('Y-m-d');
        }
    }

    // Fallback: strtotime
    $ts = @strtotime($val);
    return ($ts && $ts > 0) ? date('Y-m-d', $ts) : false;
}
