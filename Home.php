<?php
// Check if auth_token exists in cookies
if (!isset($_COOKIE['auth_token'])) {
    header("Location: Login.php");
    exit();
}

// Retrieve the token from the cookie
$authToken = $_COOKIE['auth_token'];

// Fetch users from the users.json file
$usersFile = __DIR__ . '/data/users.json'; // Path to the users JSON file


// Έλεγχος αν υπάρχει το αρχείο
if (!file_exists($usersFile)) {
    die("Το αρχείο users.json δεν βρέθηκε στο: $usersFile");
}

// Έλεγχος αν διαβάστηκαν σωστά
$users = json_decode(file_get_contents($usersFile), true);
if (!is_array($users)) {
    die("Το αρχείο users.json περιέχει μη έγκυρα δεδομένα.");
}

// Check if the user is logged in
$currentUser = null;
foreach ($users as $user) {
    if (isset($user['token']) && $user['token'] === $authToken) {
        $currentUser = $user;
        break;
    }
}

// If no user is found, redirect to login
if (!$currentUser) {
    header("Location: Login.php");
    exit();
}

// Fetch a list of friends (other users)
$friends = array_filter($users, fn($user) => $user['username'] !== $currentUser['username']);
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Αρχική - Chat Room</title>
    <link rel="stylesheet" href="css/Style.css">
    <script src="http://localhost:5500/socket.io/socket.io.js"></script>
   <script>
    const username = <?php echo json_encode($currentUser); ?>;  // Get current user's username
    // Automatically join the default room or set up a conversation based on username
    socket.emit('enterRoom', {
        name: username,
        room: 'default'  // Default room name for initial contact
    });
</script>


    <style>
        .home-container {
            max-width: 600px;
            margin: 50px auto;
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .user-list {
            list-style: none;
            padding: 0;
        }

        .user-list li {
            padding: 10px;
            border-bottom: 1px solid #ccc;
        }

        .user-list button {
            margin-top: 5px;
            padding: 5px 10px;
            background-color: #0072ff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .user-list button:hover {
            background-color: #005bb5;
        }

        .search-box {
            margin-bottom: 20px;
            padding: 10px;
            width: 100%;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        .logout-link {
            margin-top: 1rem;
            display: inline-block;
            color: #0072ff;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="home-container">
        <h2>👋 Καλωσήρθες, <?php echo htmlspecialchars($currentUser['username']); ?>!</h2>

        <! Search box for filtering users >
        <input type="text" id="search" class="search-box" placeholder="Αναζήτηση επαφών..." onkeyup="filterUsers()">

        <! List of users >
        <?php $currentUser = $_COOKIE['user'] ?? ''; ?>
       <ul id="userList">
    <?php foreach ($friends as $friend): ?>
        <?php
            $friendName = htmlspecialchars($friend['username']);
            $link = "/server/public/index.html?user=" . urlencode($currentUser) . "&target=" . urlencode($friendName);
        ?>
        <li>
            <?php echo $friendName; ?>
            <a href="<?php echo $link; ?>">💬 Start Chat</a>
        </li>
    <?php endforeach; ?>
</ul>

        <a class="logout-link" href="Logout.php">🚪 Αποσύνδεση</a>
    </div>

    <script>
    function filterUsers() {
        const input = document.getElementById("search").value.toLowerCase();
        const users = document.querySelectorAll("#userList li");

        users.forEach(li => {
            const name = li.textContent.toLowerCase();
            li.style.display = name.includes(input) ? "block" : "none";
        });
    }
    </script>
</body>
</html>
