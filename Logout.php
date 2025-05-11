<?php
// Clear the auth_token cookie
setcookie('auth_token', '', time() - 3600, '/'); // Expired cookie to delete it
header("Location: Login.php");
exit();
