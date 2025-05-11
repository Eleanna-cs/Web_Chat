<?php
// Initialize error message
$errorMessage = '';

// If the form is submitted via POST, process login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameOrEmail = trim($_POST['username_or_email'] ?? ''); // Combined field for username or email
    $password = $_POST['password'] ?? '';

    if (empty($usernameOrEmail) || empty($password)) {
        $errorMessage = "Συμπλήρωσε όλα τα πεδία.";
    } else {
        // Fetch users from the users.json file
        $usersFile = 'data/users.json'; // Path to the users JSON file

        // Ensure the file exists and can be read
        if (!file_exists($usersFile)) {
            $errorMessage = "Η βάση δεδομένων χρηστών δεν βρέθηκε.";
        } else {
            $users = json_decode(file_get_contents($usersFile), true);

            // Check if the JSON decoding was successful
            if (!is_array($users)) {
               $errorMessage = "Το αρχείο χρηστών περιέχει άκυρο JSON.";
            } else {

            // Find the user by username or email
            $user = null;
            foreach ($users as $index => $u) {
                if ($u['username'] === $usernameOrEmail || $u['email'] === $usernameOrEmail) {
                    $user = $u;
                    $userIndex = $index; // Store the user's index
                    break;
                }
            }

            // Check if user exists and if password is correct
            if ($user && password_verify($password, $user['password'])) {
                // Create a token for authentication (e.g., a random token)
                $token = bin2hex(random_bytes(16));

                // Set the token as a secure cookie with 1 hour expiration
                setcookie('auth_token', $token, time() + 3600, '/', '', true, true);

                // Store the token in the users.json file for the logged-in user
                $users[$userIndex]['token'] = $token; // Update the user's token using the index
                file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
              
                // Redirect to home page
                header('Location: Home.php');
                exit;
            } else {
                $errorMessage = "Λάθος όνομα χρήστη ή κωδικός.";
            }
        }
    }
}
}
?>

<!DOCTYPE html>
<html lang="el">
<head>
  <meta charset="UTF-8">
  <title>Σύνδεση</title>
  <link rel="stylesheet" href="css/Style.css">
</head>
<body>
  <div class="login-container">
    <h2>Σύνδεση</h2>

    <?php if (!empty($errorMessage)): ?>
      <div class="error-message"><?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>

    <form action="Login.php" method="POST">
      <input type="text" name="username_or_email" placeholder="Όνομα χρήστη ή Email" required>
      <input type="password" name="password" placeholder="Κωδικός" required>
      <button type="submit">Είσοδος</button>
    </form>

    <p>Δεν έχεις λογαριασμό; <a href="Register.php">Εγγραφή εδώ</a></p>
  </div>
</body>
</html>
