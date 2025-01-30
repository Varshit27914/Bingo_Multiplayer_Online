<?php
// create_game.php
header('Content-Type: application/json');
session_start();
include 'db_connect.php'; // Include your database connection script

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve the host's name from POST data
    $host_name = isset($_POST['host_name']) ? trim($_POST['host_name']) : '';

    if (empty($host_name)) {
        echo json_encode(['status' => 'error', 'message' => 'Host name is required.']);
        exit;
    }

    try {
        // Start a transaction
        $pdo->beginTransaction();

        // Insert a new game
        $stmt = $pdo->prepare("INSERT INTO games (host_name) VALUES (?)");
        $stmt->execute([$host_name]);
        $game_id = $pdo->lastInsertId();

        // Insert the host as a player
        $stmt = $pdo->prepare("INSERT INTO players (game_id, player_name, is_host) VALUES (?, ?, 1)");
        $stmt->execute([$game_id, $host_name]);
        $player_id = $pdo->lastInsertId();

        // Generate Bingo numbers (1-75 shuffled)
        $numbers = range(1, 25);
        shuffle($numbers);

        // Insert numbers into game_numbers table
        $stmt = $pdo->prepare("INSERT INTO game_numbers (game_id, number) VALUES (?, ?)");
        foreach ($numbers as $number) {
            $stmt->execute([$game_id, $number]);
        }

        // Generate a Bingo board for the host (random 25 numbers)
        $board_numbers = array_slice($numbers, 0, 25);
        $stmt = $pdo->prepare("INSERT INTO player_boards (player_id, number) VALUES (?, ?)");
        foreach ($board_numbers as $num) {
            $stmt->execute([$player_id, $num]);
        }

        // Commit the transaction
        $pdo->commit();

        // Respond with success and necessary data
        echo json_encode([
            'status' => 'success',
            'game_id' => $game_id,
            'player_id' => $player_id,
            'numbers' => $board_numbers
        ]);
    } catch (Exception $e) {
        // Rollback the transaction on error
        $pdo->rollBack();
        // Log the error message (for debugging; ensure not to expose in production)
        error_log("Error in create_game.php: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Failed to create the game.']);
    }
} else {
    // Invalid request method
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>
