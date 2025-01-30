<?php
// join_game.php
header('Content-Type: application/json');
session_start();
include 'db_connect.php'; // Include your database connection script

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve the game ID and player's name from POST data
    $game_id = isset($_POST['game_id']) ? intval($_POST['game_id']) : 0;
    $player_name = isset($_POST['player_name']) ? trim($_POST['player_name']) : '';

    if ($game_id <= 0 || empty($player_name)) {
        echo json_encode(['status' => 'error', 'message' => 'Game ID and player name are required.']);
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

        // Insert the player into the players table
        $stmt = $pdo->prepare("INSERT INTO players (game_id, player_name) VALUES (?, ?)");
        $stmt->execute([$game_id, $player_name]);
        $player_id = $pdo->lastInsertId();

        // Generate Bingo numbers (1-75 shuffled) if not already done
        // Assuming game_numbers are already populated during game creation

        // Fetch all numbers for the game
        $stmt = $pdo->prepare("SELECT number FROM game_numbers WHERE game_id = ? ORDER BY RAND() LIMIT 25");
        $stmt->execute([$game_id]);
        $board_numbers = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Insert numbers into player_boards table
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
        error_log("Error in join_game.php: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Failed to join the game.']);
    }
} else {
    // Invalid request method
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>
