<?php
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

$errorMessage = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $email = trim($_POST['email'] ?? '');  // Get email from the form

    // Validation checks
    if (empty($username) || empty($password) || empty($confirmPassword) || empty($email)) {
        $errorMessage = "Όλα τα πεδία είναι υποχρεωτικά.";
    } elseif ($password !== $confirmPassword) {
        $errorMessage = "Οι κωδικοί δεν ταιριάζουν.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {  // Validate email format
        $errorMessage = "Η διεύθυνση email δεν είναι έγκυρη.";
    } else {
        // Load existing users from JSON file
        $usersFile = 'data/users.json';

        // Ensure the file exists and can be read
        if (!file_exists($usersFile)) {
            $errorMessage = "Το αρχείο χρηστών δεν βρέθηκε.";
        } else {
            $users = json_decode(file_get_contents($usersFile), true);

            // Check if the username is already taken
            foreach ($users as $user) {
                if ($user['username'] === $username) {
                    $errorMessage = "Το όνομα χρήστη είναι ήδη κατειλημμένο.";
                    break;
                }
            }

            if (empty($errorMessage)) {
                // Hash password and create user object
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $newUser = [
                    'username' => $username,
                    'password' => $hashedPassword,
                    'email' => $email,  // Store email in user data
                    'token' => generateToken(16) // Token can be generated later, after login
                ];

                // Save the new user to the users file
                $users[] = $newUser;
                if (file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT))) {
                    // Redirect to login page after successful registration
                    header('Location: Login.php');
                    exit;  // Make sure to stop execution after header redirect
                } else {
                    $errorMessage = "Σφάλμα κατά την αποθήκευση του χρήστη.";
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
  <title>Εγγραφή</title>
  <link rel="stylesheet" href="css/Style.css">
</head>
<body>
  <div class="login-container">
    <h2>Εγγραφή</h2>

    <?php if (!empty($errorMessage)): ?>
      <div class="error-message"><?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>

    <form action="Register.php" method="POST">
      <input type="text" name="username" placeholder="Όνομα χρήστη" required>
      <input type="email" name="email" placeholder="Ηλεκτρονικό ταχυδρομείο" required> <!-- Email input -->
      <input type="password" name="password" placeholder="Κωδικός" required>
      <input type="password" name="confirm_password" placeholder="Επιβεβαίωση Κωδικού" required>
      <button type="submit">Εγγραφή</button>
    </form>
  </div>
</body>
</html>
