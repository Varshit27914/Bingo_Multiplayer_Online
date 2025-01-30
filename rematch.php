<?php
// rematch.php
header('Content-Type: application/json');
session_start();
include 'db_connect.php'; // Include your database connection script

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve the game ID from POST data
    $game_id = isset($_POST['game_id']) ? intval($_POST['game_id']) : 0;

    if ($game_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid game ID.']);
        exit;
    }

    try {
        // Start a transaction
        $pdo->beginTransaction();

        // Reset game status to 'waiting' and clear current_turn_player_id
        $stmt = $pdo->prepare("UPDATE games SET status = 'waiting', current_turn_player_id = NULL WHERE id = ?");
        $stmt->execute([$game_id]);

        // Reset all players' has_won status to false
        $stmt = $pdo->prepare("UPDATE players SET has_won = 0 WHERE game_id = ?");
        $stmt->execute([$game_id]);

        // Clear all selected numbers
        $stmt = $pdo->prepare("UPDATE game_numbers SET selected_by = NULL WHERE game_id = ?");
        $stmt->execute([$game_id]);

        // Optionally, reshuffle and reassign numbers
        // For simplicity, we'll keep the existing numbers

        // Commit the transaction
        $pdo->commit();

        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        // Rollback the transaction on error
        $pdo->rollBack();
        // Log the error message
        error_log("Error in rematch.php: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Failed to initiate rematch.']);
    }
} else {
    // Invalid request method
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>
