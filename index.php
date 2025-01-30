<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Multiplayer Bingo Game</title>
    <style>
        /* Basic Reset and Styling */
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f0f0f0;
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        #main-container {
            width: 90%;
            max-width: 1200px;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            border-radius: 10px;
        }

        /* Controls Section */
        #controls {
            text-align: center;
            margin-bottom: 20px;
        }

        #controls button {
            padding: 10px 20px;
            margin: 5px;
            font-size: 16px;
            cursor: pointer;
            border: none;
            border-radius: 5px;
            background-color: #4CAF50;
            color: #fff;
        }

        #controls button:hover {
            background-color: #45a049;
        }

        /* Bingo Table Styling */
        #bingoTable {
            width: 100%;
            border-collapse: collapse;
            margin: 0 auto;
        }

        #bingoTable th, #bingoTable td {
            border: 2px solid #4CAF50;
            padding: 20px;
            text-align: center;
            font-size: 18px;
            cursor: pointer;
            transition: background-color 0.3s, color 0.3s;
        }

        #bingoTable th {
            background-color: #4CAF50;
            color: white;
            cursor: default;
        }

        .crossed {
            background-color: #ccc !important;
            color: #777 !important;
            text-decoration: line-through;
            cursor: not-allowed !important;
        }

        .deactivated {
            background-color: #ccc;
            cursor: not-allowed;
        }

        /* Score and Status */
        #score, #status {
            text-align: center;
            margin-top: 10px;
            font-size: 20px;
        }

        /* Modals */
        .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1000; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgba(0,0,0,0.5); /* Black w/ opacity */
        }

        .modal-content {
            background-color: #fefefe;
            margin: 10% auto; /* 10% from the top and centered */
            padding: 20px;
            border: 1px solid #888;
            width: 90%;
            max-width: 500px;
            border-radius: 10px;
            text-align: center;
        }

        .modal-content input, .modal-content select {
            width: 80%;
            padding: 10px;
            margin: 10px 0;
            font-size: 16px;
        }

        .modal-content button {
            padding: 10px 20px;
            margin: 10px;
            font-size: 16px;
            cursor: pointer;
            border: none;
            border-radius: 5px;
            background-color: #4CAF50;
            color: #fff;
        }

        .modal-content button:hover {
            background-color: #45a049;
        }

        /* Win Modal */
        #winModal h2 {
            color: green;
        }

        /* Start Game Button (only visible to host when game is ready) */
        #startGameBtn {
            display: none;
            background-color: #008CBA;
        }

        #startGameBtn:hover {
            background-color: #007bb5;
        }#playersList {
            margin-top: 20px;
            text-align: center;
        }

        #playersList h3 {
            margin-bottom: 10px;
        }

        #playersList ul {
            list-style-type: none;
            padding: 0;
        }

        #playersList li {
            padding: 5px 0;
            font-size: 18px;
        }

        /* Optional: Differentiate Host */
        .host {
            font-weight: bold;
            color: #4CAF50;
        }

        .winner {
            color: gold;
        }
    </style>
</head>
<body>

<div id="main-container">
    <!-- Controls Section -->
    <div id="controls">
        <button onclick="openModal('hostModal')">Host Game</button>
        <button onclick="openModal('joinModal')">Join Game</button>
        <button id="startGameBtn" onclick="startGame()">Start Game</button>
    </div>
<!-- Players List Section -->
<div id="playersList" style="display:none;">
    <h3>Players in the Game:</h3>
    <ul id="playersUl">
        <!-- Player names will be dynamically populated here -->
    </ul>
</div>
    <!-- Bingo Game Section -->
    <div id="game-container" style="display:none;">
    <table id="bingoTable">
        <tr>
            <th></th>
            <th>B</th>
            <th>I</th>
            <th>N</th>
            <th>G</th>
            <th>O</th>
        </tr>
        <!-- Bingo numbers will be dynamically generated here -->
    </table>

    <div id="score">Your Score = <span id="scoreValue">0</span></div> 
    <div id="status"></div>
</div>

    

<!-- Host Game Modal -->
<div id="hostModal" class="modal">
    <div class="modal-content">
        <h2>Host a Game</h2>
        <input type="text" id="hostName" placeholder="Enter your name" required>
        <br>
        <button onclick="hostGame()">Create Game</button>
        <button onclick="closeModal('hostModal')">Cancel</button>
    </div>
</div>

<!-- Join Game Modal -->
<div id="joinModal" class="modal">
    <div class="modal-content">
        <h2>Join a Game</h2>
        <input type="text" id="playerName" placeholder="Enter your name" required>
        <br>
        <select id="availableGames">
            <option value="">Select a game</option>
            <!-- Available games will be populated here -->
        </select>
        <br>
        <button onclick="joinGame()">Join Game</button>
        <button onclick="closeModal('joinModal')">Cancel</button>
    </div>
</div>

<!-- Win Modal -->
<div id="winModal" class="modal">
    <div class="modal-content">
        <h2 id="winMessage">You Win!</h2>
        <p id="winDetails">Congratulations! You have won the game.</p>
        <button onclick="rematch()">Rematch</button>
        <button onclick="exitGame()">Exit</button>
    </div>
</div>

<script>
    // Global Variables
    let playerId = null;
    let gameId = null;
    let isHost = false;
    let bingoNumbers = [];
    let playerName = '';
    let currentTurnPlayerId = null;
    let gameStarted = false;
    let pollingInterval = null;

    // Initialize the game board
    function initializeBoard(numbers) {
        const table = document.getElementById("bingoTable");

        // Clear existing rows except the header
        while (table.rows.length > 1) {
            table.deleteRow(1);
        }

        // Populate the board with numbers
        for (let i = 0; i < 5; i++) {
            const row = table.insertRow();
            for (let j = 0; j < 6; j++) {
                const cell = row.insertCell(j);
                if (j === 0) {
                    cell.innerText = "BINGO"[i];
                    cell.classList.add("header");
                } else {
                    const number = numbers[i * 5 + (j - 1)];
                    cell.innerText = number;
                    cell.setAttribute('data-number', number);
                    cell.addEventListener("click", onNumberClick);
                }
            }
        }
    }

    // Open Modal
    function openModal(modalId) {
        document.getElementById(modalId).style.display = "block";
        if (modalId === 'joinModal') {
            fetchAvailableGames();
        }
    }

    // Close Modal
    function closeModal(modalId) {
        document.getElementById(modalId).style.display = "none";
    }

    // Host a Game
    function hostGame() {
    const hostNameInput = document.getElementById('hostName');
    const hostNameValue = hostNameInput.value.trim();

    if (hostNameValue === '') {
        alert("Please enter your name.");
        return;
    }

    // Send POST request to create_game.php
    const formData = new FormData();
    formData.append('host_name', hostNameValue);

    fetch('create_game.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            playerId = data.player_id; // Assuming create_game.php returns player_id
            gameId = data.game_id;
            isHost = true;
            playerName = hostNameValue;
            console.log(`Host Game: playerId=${playerId}, gameId=${gameId}`);
            closeModal('hostModal');
            document.getElementById('game-container').style.display = "block";
            document.getElementById('startGameBtn').style.display = "inline-block";
            initializeBoard(data.numbers); // Assuming create_game.php returns initial numbers
            startPolling();
        } else {
            alert(data.message || "Failed to host the game.");
        }
    })
    .catch(error => {
        console.error('Error hosting game:', error);
        alert("An error occurred while hosting the game.");
    });
}

fetch('select_number.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: new URLSearchParams({
        game_id: gameId,
        player_id: playerId,
        number: selectedNumber
    })
})
.then(response => response.json())
.then(data => {
    if (data.status === 'success') {
        // Update score display
        document.getElementById('scoreValue').innerText = data.win_count; // Update to use win_count

        if (data.has_won) {
            document.getElementById('status').innerText = "Congratulations! You've won!";
        } else {
            document.getElementById('status').innerText = "Next player's turn.";
        }
    } else {
        alert(data.message); // Handle error messages
    }
})
.catch(error => {
    console.error('Error:', error);
});



    // Join a Game
    function joinGame() {
    const playerNameInput = document.getElementById('playerName');
    const playerNameValue = playerNameInput.value.trim();
    const selectedGameId = document.getElementById('availableGames').value;

    if (playerNameValue === '') {
        alert("Please enter your name.");
        return;
    }

    if (selectedGameId === '') {
        alert("Please select a game to join.");
        return;
    }

    // Send POST request to join_game.php
    const formData = new FormData();
    formData.append('game_id', selectedGameId);
    formData.append('player_name', playerNameValue);

    fetch('join_game.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            playerId = data.player_id; // Assuming join_game.php returns player_id
            gameId = data.game_id;
            isHost = false;
            playerName = playerNameValue;
            console.log(`Join Game: playerId=${playerId}, gameId=${gameId}`);
            closeModal('joinModal');
            document.getElementById('game-container').style.display = "block";
            initializeBoard(data.numbers); // Assuming join_game.php returns initial numbers
            startPolling();
        } else {
            alert(data.message || "Failed to join the game.");
        }
    })
    .catch(error => {
        console.error('Error joining game:', error);
        alert("An error occurred while joining the game.");
    });
}


    // Fetch Available Games
    function fetchAvailableGames() {
        fetch('list_games.php')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const availableGamesSelect = document.getElementById('availableGames');
                    availableGamesSelect.innerHTML = '<option value="">Select a game</option>';
                    data.games.forEach(game => {
                        const option = document.createElement('option');
                        option.value = game.id;
                        option.text = `Game ${game.id} hosted by ${game.host_name}`;
                        availableGamesSelect.appendChild(option);
                    });
                } else {
                    alert(data.message || "Failed to fetch available games.");
                }
            })
            .catch(error => {
                console.error('Error fetching available games:', error);
                alert("An error occurred while fetching available games.");
            });
    }

    // Start the Game (Host Only)
    function startGame() {
    if (!isHost) return;

    const formData = new FormData();
    formData.append('game_id', gameId);

    fetch('start_game.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status == 'success') {
            currentTurnPlayerId = playerId; // Set host as the first player
            document.getElementById('startGameBtn').style.display = "none";
            gameStarted = true;
            updateStatus("Game Started! Waiting for your turn.");
        } else {
            alert(data.message || "Failed to start the game.");
        }
    })
    .catch(error => {
        console.error('Error starting the game:', error);
        alert("An error occurred while starting the game.");
    });
}


    // Handle Number Click
    function onNumberClick(event) {
    const number = parseInt(event.target.getAttribute('data-number'));

    console.log(`Attempting to select number: ${number}`);
    console.log(`Your Player ID: ${playerId}`);
    console.log(`Current Turn Player ID: ${currentTurnPlayerId}`);

    if (!gameStarted) {
        alert("Game has not started yet.");
        return;
    }

    if (currentTurnPlayerId != playerId) { // Using != as per your change
        console.log(`Not your turn. currentTurnPlayerId (${currentTurnPlayerId}) != playerId (${playerId})`);
        alert("It's not your turn.");
        return;
    }

    // Send POST request to select_number.php
    const formData = new FormData();
    formData.append('game_id', gameId);
    formData.append('player_id', playerId);
    formData.append('number', number);

    fetch('select_number.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Number successfully selected
            event.target.classList.add('crossed', 'deactivated');
            updateStatus("Number selected. Waiting for next turn.");
            console.log(`Number ${number} selected successfully.`);
        } else {
            alert(data.message || "Failed to select the number.");
            console.log(`Failed to select number: ${number}. Reason: ${data.message}`);
        }
    })
    .catch(error => {
        console.error('Error selecting number:', error);
        alert("An error occurred while selecting the number.");
    });
}



    // Polling to Fetch Game State
    function startPolling() {
        pollingInterval = setInterval(fetchGameState, 2000);
    }

    // Fetch Game State
    function fetchGameState() {
    if (!gameId) return;

    fetch(`game_state.php?game_id=${gameId}`)
        .then(response => response.text()) // Get response as text first
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.status === 'success') {
                    console.log("Received game state:", data);
                    updateGameUI(data);
                } else {
                    console.error('Error in game_state.php:', data.message);
                    alert(data.message || "Failed to fetch game state.");
                }
            } catch (e) {
                console.error('Error parsing JSON:', e);
                console.error('Response text:', text);
                alert("An error occurred while processing game state.");
            }
        })
        .catch(error => {
            console.error('Error fetching game state:', error);
            alert("An error occurred while fetching game state.");
        });
}


    // Update Game UI Based on Game State
    function updateGameUI(data) {
    const game = data.game;
    const players = data.players;
    const selected_numbers = data.selected_numbers;

    console.log("Game Status:", game.status);
    console.log("Current Turn Player ID:", game.current_turn_player_id);
    console.log("Your Player ID:", playerId);

    // Update current turn
    currentTurnPlayerId = game.current_turn_player_id;

    // Update the game board
    selected_numbers.forEach(item => {
        const number = item.number;
        const cells = document.querySelectorAll(`td[data-number="${number}"]`);
        cells.forEach(cell => {
            cell.classList.add('crossed', 'deactivated');
        });
    });

    // Check if the game has started
    if (game.status === 'started') {
        gameStarted = true;
        document.getElementById('playersList').style.display = "block";
    }

    // Update status message
    if (game.status === 'waiting') {
        updateStatus("Waiting for players to join...");
        document.getElementById('playersList').style.display = "block";
    } else if (game.status === 'started') {
        if (currentTurnPlayerId === playerId) {
            updateStatus("It's your turn!");
        } else {
            const currentPlayer = players.find(p => p.id === currentTurnPlayerId);
            if (currentPlayer) {
                updateStatus(`Waiting for ${currentPlayer.player_name}'s turn.`);
            }
        }
    } else if (game.status === 'ended') {
        // Handle game end
        const winners = players.filter(p => p.has_won);
        if (winners.length > 0) {
            const winnerNames = winners.map(w => w.player_name).join(', ');
            if (winnerNames.includes(playerName)) {
                showWinMessage("You have won the game!");
            } else {
                showWinMessage(`${winnerNames} have won the game.`);
            }
        }
        clearInterval(pollingInterval);
    }

    // Show Start Game button to host if the game is still waiting
    if (isHost && game.status === 'waiting') {
        document.getElementById('startGameBtn').style.display = "inline-block";
    } else {
        document.getElementById('startGameBtn').style.display = "none";
    }

    // Update Players List
    updatePlayersList(players);
}
    const game = data.game;
    const players = data.players;
    const selected_numbers = data.selected_numbers;

    console.log("Game Status:", game.status);
    console.log("Current Turn Player ID:", game.current_turn_player_id);
    console.log("Your Player ID:", playerId);

    // Update current turn
    currentTurnPlayerId = game.current_turn_player_id;

    // Update the game board
    selected_numbers.forEach(item => {
        const number = item.number;
        const cells = document.querySelectorAll(`td[data-number="${number}"]`);
        cells.forEach(cell => {
            cell.classList.add('crossed', 'deactivated');
        });
    });
    function updatePlayersList(players) {
        const playersUl = document.getElementById('playersUl');
        playersUl.innerHTML = ''; // Clear existing list

        players.forEach(player => {
            const li = document.createElement('li');
            li.textContent = player.player_name;
            if (player.is_host) {
                li.classList.add('host');
                li.textContent += " (Host)";
            }
            if (player.has_won) {
                li.classList.add('winner');
                li.textContent += " - Won";
            }
            playersUl.appendChild(li);
        });
    }
    // Check if the game has started
    if (game.status === 'started') {
        gameStarted = true;
    }

    // Update status message
    if (game.status === 'waiting') {
        updateStatus("Waiting for players to join...");
    } else if (game.status === 'started') {
        if (currentTurnPlayerId === playerId) {
            updateStatus("It's your turn!");
        } else {
            const currentPlayer = players.find(p => p.id === currentTurnPlayerId);
            if (currentPlayer) {
                updateStatus(`Waiting for ${currentPlayer.player_name}'s turn.`);
            }
        }
    } else if (game.status === 'ended') {
        // Handle game end
        const winners = players.filter(p => p.has_won);
        if (winners.length > 0) {
            const winnerNames = winners.map(w => w.player_name).join(', ');
            showWinMessage(winnerNames);
        }
        clearInterval(pollingInterval);
    }

    // Show Start Game button to host if the game is still waiting
    if (isHost && game.status === 'waiting') {
        document.getElementById('startGameBtn').style.display = "inline-block";
    } else {
        document.getElementById('startGameBtn').style.display = "none";
    }



    // Update Status Message
    function updateStatus(message) {
        document.getElementById('status').innerText = message;
    }

    // Show Win Message Modal
    function showWinMessage(winnerNames) {
        const winModal = document.getElementById('winModal');
        const winMessage = document.getElementById('winMessage');
        const winDetails = document.getElementById('winDetails');

        if (winnerNames.includes(playerName)) {
            winMessage.innerText = "You Win!";
        } else {
            winMessage.innerText = "Game Over!";
        }

        winDetails.innerText = `Winner(s): ${winnerNames}`;
        winModal.style.display = "block";
    }

    // Rematch Functionality
    function rematch() {
        // Send POST request to rematch.php
        const formData = new FormData();
        formData.append('game_id', gameId);

        fetch('rematch.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Reset the game UI
                initializeBoard(Array.from({ length: 25 }, (_, i) => i + 1)); // Reset board numbers
                document.getElementById('winModal').style.display = "none";
                gameStarted = false;
                updateStatus("Rematch started! Waiting for the host to start the game.");
                if (isHost) {
                    document.getElementById('startGameBtn').style.display = "inline-block";
                }
            } else {
                alert(data.message || "Failed to start a rematch.");
            }
        })
        .catch(error => {
            console.error('Error requesting rematch:', error);
            alert("An error occurred while requesting a rematch.");
        });
    }

    // Exit Game Functionality
    function exitGame() {
        // Send POST request to exit_game.php
        const formData = new FormData();
        formData.append('game_id', gameId);
        formData.append('player_id', playerId);

        fetch('exit_game.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                alert("You have exited the game.");
                window.location.reload();
            } else {
                alert(data.message || "Failed to exit the game.");
            }
        })
        .catch(error => {
            console.error('Error exiting game:', error);
            alert("An error occurred while exiting the game.");
        });
    }

    // Close Modals When Clicking Outside of Them
    window.onclick = function(event) {
        const modals = document.getElementsByClassName('modal');
        for (let modal of modals) {
            if (event.target === modal) {
                modal.style.display = "none";
            }
        }
    }

    // Initialize the game board with default numbers (1-25) before joining or hosting
    initializeBoard(Array.from({ length: 25 }, (_, i) => i + 1));

</script>

</body>
</html>
