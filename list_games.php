<?php
// list_games.php
header('Content-Type: application/json');
session_start();
include 'db_connect.php'; // Include your database connection script

try {
    // Fetch all games with status 'waiting'
    $stmt = $pdo->prepare("SELECT id, host_name, created_at FROM games WHERE status = 'waiting'");
    $stmt->execute();
    $games = $stmt->fetchAll();

    echo json_encode([
        'status' => 'success',
        'games' => $games
    ]);
} catch (Exception $e) {
    // Log the error message
    error_log("Error in list_games.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Failed to list available games.']);
}
?>
