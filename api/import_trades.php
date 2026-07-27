<?php
session_start();
require_once '../config/db.php';

function redirectToImportPage($message, $errors = []) {
    $_SESSION['import_message'] = $message;
    $_SESSION['import_errors'] = $errors;
    header("Location: ../import_trades.php");
    exit;
}

function isValidDate($date) {
    $format = 'Y-m-d';
    $dateTime = DateTime::createFromFormat($format, $date);
    return $dateTime && $dateTime->format($format) === $date;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectToImportPage("Invalid request.");
}

if (!isset($_FILES['trade_file']) || $_FILES['trade_file']['error'] !== 0) {
    redirectToImportPage("Please upload a valid CSV file.");
}

$fileName = $_FILES['trade_file']['name'];
$fileTmpPath = $_FILES['trade_file']['tmp_name'];
$fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

if ($fileExtension !== 'csv') {
    redirectToImportPage("Only CSV files are allowed.");
}

try {
    $pdo = getDB();
    $userId = $_SESSION['user_id'] ?? 1;

    $file = fopen($fileTmpPath, 'r');

    if ($file === false) {
        redirectToImportPage("Could not open CSV file.");
    }

    $header = fgetcsv($file);

    if ($header === false) {
        redirectToImportPage("CSV file is empty.");
    }

    $insertedRows = 0;
    $errors = [];
    $rowNumber = 1;

    $sql = "INSERT INTO trades 
            (user_id, asset_name, trade_type, entry_price, exit_price, quantity, trade_date, notes, emotion, emotion_note)
            VALUES 
            (:user_id, :asset_name, :trade_type, :entry_price, :exit_price, :quantity, :trade_date, :notes, :emotion, :emotion_note)";

    $stmt = $pdo->prepare($sql);

    while (($row = fgetcsv($file)) !== false) {
        $rowNumber++;

        if (count($row) < 9) {
            $errors[] = "Row $rowNumber error: Missing required columns.";
            continue;
        }

        $assetName = trim($row[0]);
        $tradeType = trim($row[1]);
        $entryPrice = trim($row[2]);
        $exitPrice = trim($row[3]);
        $quantity = trim($row[4]);
        $tradeDate = trim($row[5]);
        $notes = trim($row[6]);
        $emotion = trim($row[7]);
        $emotionNote = trim($row[8]);

        if ($assetName === '') {
            $errors[] = "Row $rowNumber error: Asset name is missing.";
            continue;
        }

        if ($tradeType !== 'Buy' && $tradeType !== 'Sell') {
            $errors[] = "Row $rowNumber error: Trade type must be Buy or Sell.";
            continue;
        }

        if (!is_numeric($entryPrice) || $entryPrice <= 0) {
            $errors[] = "Row $rowNumber error: Entry price must be a positive number.";
            continue;
        }

        if (!is_numeric($exitPrice) || $exitPrice <= 0) {
            $errors[] = "Row $rowNumber error: Exit price must be a positive number.";
            continue;
        }

        if (!is_numeric($quantity) || $quantity <= 0) {
            $errors[] = "Row $rowNumber error: Quantity must be a positive number.";
            continue;
        }

        if (!isValidDate($tradeDate)) {
            $errors[] = "Row $rowNumber error: Trade date must be in YYYY-MM-DD format.";
            continue;
        }

        $stmt->execute([
            ':user_id' => $userId,
            ':asset_name' => $assetName,
            ':trade_type' => $tradeType,
            ':entry_price' => $entryPrice,
            ':exit_price' => $exitPrice,
            ':quantity' => $quantity,
            ':trade_date' => $tradeDate,
            ':notes' => $notes,
            ':emotion' => $emotion,
            ':emotion_note' => $emotionNote
        ]);

        $insertedRows++;
    }

    fclose($file);

    if (!empty($errors)) {
        redirectToImportPage("Imported $insertedRows trades. Some rows had errors.", $errors);
    }

    redirectToImportPage("Successfully imported $insertedRows trades.");

} catch (Exception $e) {
    redirectToImportPage("Import failed. Please check your CSV file and database connection.");
}
?>