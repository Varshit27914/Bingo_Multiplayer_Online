<?php
// check_win.php
session_start();
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $game_id = $_POST['game_id'];
    $player_id = $_POST['player_id'];

    // Fetch selected numbers by the player
    $stmt = $pdo->prepare("
        SELECT gn.number
        FROM game_numbers gn
        JOIN players p ON gn.selected_by = p.id
        WHERE gn.game_id = ? AND p.id = ?
    ");
    $stmt->execute([$game_id, $player_id]);
    $selected_numbers = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Define Bingo win conditions (e.g., complete row, column, diagonal)
    // This requires knowledge of the player's board, which should be stored in the database
    // For simplicity, assume you have a `player_boards` table storing each player's board

    // Example logic:
    // 1. Fetch player's board
    // 2. Check if any row, column, or diagonal is fully in selected_numbers
    // 3. If yes, update player's has_won status

    // Placeholder for actual win condition checking
    $has_won = false; // Replace with actual condition

    if ($has_won) {
        $stmt = $pdo->prepare("UPDATE players SET has_won = 1 WHERE id = ?");
        $stmt->execute([$player_id]);
        echo json_encode(['status' => 'success', 'has_won' => true]);
    } else {
        echo json_encode(['status' => 'success', 'has_won' => false]);
    }
}
?>
