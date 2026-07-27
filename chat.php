<?php
require_once 'config/db.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>TradeLens AI Assistant</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <h2>TradeLens AI Assistant</h2>
    <p>This page shows the AI chatbot interface for trading feedback.</p>

    <?php include 'chat_widget.php'; ?>

    <script src="main.js"></script>
</body>
</html>