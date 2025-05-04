<?php
declare(strict_types=1);

require_once 'Database.php';
require_once 'WortspielJob.php';

$db = new Database();
$pdo = $db->getConnection();

if ($pdo !== null) {
    $job = new WortspielJob($pdo);
    $job->createTable();
    $job->processUnknownTransactions(53, 98);
    $db->close();
} else {
    echo "Could not create WortspielJob due to missing database connection.\n";
}
