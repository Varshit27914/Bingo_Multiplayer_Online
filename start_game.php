<?php
// start_game.php
header('Content-Type: application/json');
session_start();
include 'db_connect.php'; // Ensure the path is correct

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

        // Check if the game exists and is in 'waiting' status
        $stmt = $pdo->prepare("SELECT * FROM games WHERE id = ? AND status = 'waiting'");
        $stmt->execute([$game_id]);
        $game = $stmt->fetch();

        if (!$game) {
            echo json_encode(['status' => 'error', 'message' => 'Game not found or already started.']);
            $pdo->rollBack();
            exit;
        }

        // Fetch all players in the game, prioritize host
        $stmt = $pdo->prepare("SELECT id FROM players WHERE game_id = ? ORDER BY is_host DESC, id ASC");
        $stmt->execute([$game_id]);
        $players = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (count($players) == 0) {
            echo json_encode(['status' => 'error', 'message' => 'No players in the game.']);
            $pdo->rollBack();
            exit;
        }

        // Set the first player (host) as the current turn
        $current_turn_player_id = $players[0];
        $stmt = $pdo->prepare("UPDATE games SET status = 'started', current_turn_player_id = ? WHERE id = ?");
        $stmt->execute([$current_turn_player_id, $game_id]);

        // Commit the transaction
        $pdo->commit();

        // Log the turn initialization
        error_log("Game ID: $game_id started. Current turn set to Player ID: $current_turn_player_id");

        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        // Rollback the transaction on error
        $pdo->rollBack();
        // Log the error message
        error_log("Error in start_game.php: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Failed to start the game.']);
    }
} else {
    // Invalid request method
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>
