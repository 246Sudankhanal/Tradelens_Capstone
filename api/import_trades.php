<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['trade_file']) && $_FILES['trade_file']['error'] === 0) {
        echo "CSV file uploaded successfully. Import feature will be completed in Week 3.";
    } else {
        echo "Please select a valid CSV file.";
    }
} else {
    echo "Invalid request.";
}

?>