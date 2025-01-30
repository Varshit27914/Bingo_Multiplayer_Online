<?php
// exit_game.php
header('Content-Type: application/json');
session_start();
include 'db_connect.php'; // Include your database connection script

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve the game ID and player ID from POST data
    $game_id = isset($_POST['game_id']) ? intval($_POST['game_id']) : 0;
    $player_id = isset($_POST['player_id']) ? intval($_POST['player_id']) : 0;

    if ($game_id <= 0 || $player_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid game ID or player ID.']);
        exit;
    }

    try {
        // Start a transaction
        $pdo->beginTransaction();

        // Fetch the player to check if they are the host
        $stmt = $pdo->prepare("SELECT is_host FROM players WHERE id = ? AND game_id = ?");
        $stmt->execute([$player_id, $game_id]);
        $player = $stmt->fetch();

        if (!$player) {
            echo json_encode(['status' => 'error', 'message' => 'Player not found in the game.']);
            $pdo->rollBack();
            exit;
        }

        $is_host = $player['is_host'];

        // Delete the player from the players table
        $stmt = $pdo->prepare("DELETE FROM players WHERE id = ?");
        $stmt->execute([$player_id]);

        if ($is_host) {
            // If the host leaves, assign a new host or end the game
            // Fetch another player to assign as host
            $stmt = $pdo->prepare("SELECT id FROM players WHERE game_id = ? ORDER BY id ASC");
            $stmt->execute([$game_id]);
            $new_host = $stmt->fetch();

            if ($new_host) {
                // Assign the new host
                $stmt = $pdo->prepare("UPDATE players SET is_host = 1 WHERE id = ?");
                $stmt->execute([$new_host['id']]);

                // Update the game's host_name
                $stmt = $pdo->prepare("SELECT player_name FROM players WHERE id = ?");
                $stmt->execute([$new_host['id']]);
                $new_host_name = $stmt->fetchColumn();

                $stmt = $pdo->prepare("UPDATE games SET host_name = ? WHERE id = ?");
                $stmt->execute([$new_host_name, $game_id]);
            } else {
                // No players left; delete the game
                $stmt = $pdo->prepare("DELETE FROM games WHERE id = ?");
                $stmt->execute([$game_id]);
            }
        }

        // If no players are left, the game is deleted due to ON DELETE CASCADE

        // Commit the transaction
        $pdo->commit();

        echo json_encode(['status' => 'success', 'message' => 'You have exited the game.']);
    } catch (Exception $e) {
        // Rollback the transaction on error
        $pdo->rollBack();
        // Log the error message
        error_log("Error in exit_game.php: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Failed to exit the game.']);
    }
} else {
    // Invalid request method
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>
