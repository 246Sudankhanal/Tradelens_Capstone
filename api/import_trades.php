<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../import_trades.php?status=error&message=Invalid request");
    exit;
}

if (!isset($_FILES['trade_file']) || $_FILES['trade_file']['error'] !== 0) {
    header("Location: ../import_trades.php?status=error&message=Please upload a valid CSV file");
    exit;
}

$fileName = $_FILES['trade_file']['name'];
$fileTmpPath = $_FILES['trade_file']['tmp_name'];
$fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

if ($fileExtension !== 'csv') {
    header("Location: ../import_trades.php?status=error&message=Only CSV files are allowed");
    exit;
}

try {
    $pdo = getDB();

    // temporary user id for testing
    $userId = $_SESSION['user_id'] ?? 1;

    $file = fopen($fileTmpPath, 'r');

    if ($file === false) {
        header("Location: ../import_trades.php?status=error&message=Could not open CSV file");
        exit;
    }

    // Skip header row
    fgetcsv($file);

    $insertedRows = 0;

    $sql = "INSERT INTO trades 
            (user_id, asset_name, trade_type, entry_price, exit_price, quantity, trade_date, notes, emotion, emotion_note)
            VALUES 
            (:user_id, :asset_name, :trade_type, :entry_price, :exit_price, :quantity, :trade_date, :notes, :emotion, :emotion_note)";

    $stmt = $pdo->prepare($sql);

    while (($row = fgetcsv($file)) !== false) {
        if (count($row) < 9) {
            continue;
        }

        $stmt->execute([
            ':user_id' => $userId,
            ':asset_name' => trim($row[0]),
            ':trade_type' => trim($row[1]),
            ':entry_price' => trim($row[2]),
            ':exit_price' => trim($row[3]),
            ':quantity' => trim($row[4]),
            ':trade_date' => trim($row[5]),
            ':notes' => trim($row[6]),
            ':emotion' => trim($row[7]),
            ':emotion_note' => trim($row[8])
        ]);

        $insertedRows++;
    }

    fclose($file);

    header("Location: ../import_trades.php?status=success&message=Successfully imported $insertedRows trades");
    exit;

} catch (Exception $e) {
    header("Location: ../import_trades.php?status=error&message=Import failed");
    exit;
}
?>