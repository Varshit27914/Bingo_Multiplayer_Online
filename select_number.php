<?php
// select_number.php

header('Content-Type: application/json');
session_start();
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve data from POST
    $game_id = isset($_POST['game_id']) ? intval($_POST['game_id']) : 0;
    $player_id = isset($_POST['player_id']) ? intval($_POST['player_id']) : 0;
    $number = isset($_POST['number']) ? intval($_POST['number']) : 0;

    if ($game_id <= 0 || $player_id <= 0 || $number <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid input data.']);
        exit;
    }

    try {
        // Start a transaction
        $pdo->beginTransaction();

        // Check if the game exists and is 'started'
        $stmt = $pdo->prepare("SELECT * FROM games WHERE id = ? AND status = 'started'");
        $stmt->execute([$game_id]);
        $game = $stmt->fetch();

        if (!$game) {
            echo json_encode(['status' => 'error', 'message' => 'Game not found or not started.']);
            $pdo->rollBack();
            exit;
        }

        // Check if it's the player's turn
        if ($game['current_turn_player_id'] != $player_id) {
            echo json_encode(['status' => 'error', 'message' => 'It is not your turn.']);
            $pdo->rollBack();
            exit;
        }

        // Check if the number is part of the player's board
        $stmt = $pdo->prepare("SELECT * FROM player_boards WHERE player_id = ? AND number = ?");
        $stmt->execute([$player_id, $number]);
        $player_number = $stmt->fetch();

        if (!$player_number) {
            echo json_encode(['status' => 'error', 'message' => 'The number is not on your board.']);
            $pdo->rollBack();
            exit;
        }

        // Check if the number has already been selected
        $stmt = $pdo->prepare("SELECT * FROM game_numbers WHERE game_id = ? AND number = ?");
        $stmt->execute([$game_id, $number]);
        $game_number = $stmt->fetch();

        if (!$game_number || $game_number['selected_by'] !== null) {
            echo json_encode(['status' => 'error', 'message' => 'Number not available.']);
            $pdo->rollBack();
            exit;
        }

        // Mark the number as selected by the player
        $stmt = $pdo->prepare("UPDATE game_numbers SET selected_by = ? WHERE id = ?");
        $stmt->execute([$player_id, $game_number['id']]);

        // Fetch all selected numbers from all players
        $stmt = $pdo->prepare("SELECT number FROM game_numbers WHERE game_id = ? AND selected_by IS NOT NULL");
        $stmt->execute([$game_id]);
        $selected_numbers = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Fetch player's board numbers
        $stmt = $pdo->prepare("SELECT number FROM player_boards WHERE player_id = ?");
        $stmt->execute([$player_id]);
        $board_numbers = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Initialize win count
        $win_count = 0;
        $board_size = 5; // Assuming a 5x5 board

        // Check rows and columns
        for ($i = 0; $i < $board_size; $i++) {
            $row = array_slice($board_numbers, $i * $board_size, $board_size);
            if (count(array_intersect($selected_numbers, $row)) == $board_size) {
                $win_count++;
            }

            $column = [];
            for ($j = 0; $j < $board_size; $j++) {
                $column[] = $board_numbers[$i + $j * $board_size];
            }
            if (count(array_intersect($selected_numbers, $column)) == $board_size) {
                $win_count++;
            }
        }

        // Check diagonals
        $diagonal1 = [];
        $diagonal2 = [];
        for ($i = 0; $i < $board_size; $i++) {
            $diagonal1[] = $board_numbers[$i * ($board_size + 1)];
            $diagonal2[] = $board_numbers[($i + 1) * ($board_size - 1)];
        }

        if (count(array_intersect($selected_numbers, $diagonal1)) == $board_size) {
            $win_count++;
        }
        if (count(array_intersect($selected_numbers, $diagonal2)) == $board_size) {
            $win_count++;
        }

        $has_won = $win_count >= 5; // Win if 5 lines are completed

        if ($has_won) {
            // Update player's has_won status
            $stmt = $pdo->prepare("UPDATE players SET has_won = 1 WHERE id = ?");
            $stmt->execute([$player_id]);

            // Update game status to 'ended'
            $stmt = $pdo->prepare("UPDATE games SET status = 'ended' WHERE id = ?");
            $stmt->execute([$game_id]);
        } else {
            // Determine the next player's turn
            $stmt = $pdo->prepare("SELECT id FROM players WHERE game_id = ? ORDER BY id ASC");
            $stmt->execute([$game_id]);
            $players = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $current_index = array_search($player_id, $players);
            $next_index = ($current_index + 1) % count($players);
            $next_player_id = $players[$next_index];

            // Update the current_turn_player_id
            $stmt = $pdo->prepare("UPDATE games SET current_turn_player_id = ? WHERE id = ?");
            $stmt->execute([$next_player_id, $game_id]);
        }

        // Commit the transaction
        $pdo->commit();

        // Respond with success
        echo json_encode(['status' => 'success', 'has_won' => $has_won, 'score' => $win_count]);
    } catch (Exception $e) {
        // Rollback the transaction on error
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Failed to select the number: ' . $e->getMessage()]);
    }
} else {
    // Invalid request method
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
