<?php
// game_state.php

header('Content-Type: application/json');
session_start();
include 'db_connect.php'; // Ensure the path is correct

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $game_id = isset($_GET['game_id']) ? intval($_GET['game_id']) : 0;

    if ($game_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid game ID.']);
        exit;
    }

    try {
        // Fetch game details
        $stmt = $pdo->prepare("SELECT * FROM games WHERE id = ?");
        $stmt->execute([$game_id]);
        $game = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$game) {
            echo json_encode(['status' => 'error', 'message' => 'Game not found.']);
            exit;
        }

        // Fetch players in the game
        $stmt = $pdo->prepare("SELECT id, player_name, is_host, has_won FROM players WHERE game_id = ?");
        $stmt->execute([$game_id]);
        $players = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch selected numbers
        $stmt = $pdo->prepare("SELECT number, selected_by FROM game_numbers WHERE game_id = ? AND selected_by IS NOT NULL");
        $stmt->execute([$game_id]);
        $selected_numbers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Prepare response
        $response = [
            'status' => 'success',
            'game' => [
                'id' => intval($game['id']),
                'host_name' => $game['host_name'],
                'status' => $game['status'],
                'current_turn_player_id' => intval($game['current_turn_player_id']),
                'created_at' => $game['created_at']
            ],
            'players' => $players,
            'selected_numbers' => $selected_numbers
        ];

        echo json_encode($response);
    } catch (Exception $e) {
        // Log the error
        error_log("Error in game_state.php: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Failed to fetch game state.']);
    }
} else {
    // Invalid request method
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>
