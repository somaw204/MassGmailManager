<?php
session_start();

// Hardcoded login credentials
$valid_username = 'Random';
$valid_password = 'Random';

// Check if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username === $valid_username && $password === $valid_password) {
        $_SESSION['logged_in'] = true;
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <style>
        body {
            background-color: #121212;
            color: #f0f0f0;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        form {
            background-color: #1f1f1f;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px #000;
        }
        input {
            display: block;
            margin-bottom: 15px;
            padding: 10px;
            width: 100%;
            border: none;
            border-radius: 5px;
            background-color: #2b2b2b;
            color: #f0f0f0;
        }
        button {
            padding: 10px;
            width: 100%;
            background-color: #3a3a3a;
            border: none;
            color: white;
            cursor: pointer;
        }
        button:hover {
            background-color: #555;
        }
        .error {
            color: #ff5050;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<form method="post">
    <h2>Login</h2>
    <?php if (isset($error)) echo "<div class='error'>" . htmlspecialchars($error) . "</div>"; ?>
    <input type="text" name="username" placeholder="Username" required autofocus>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit">Login</button>
</form>

</body>
</html>
