<?php
session_start();

// If already logged in, redirect to the home page
if (isset($_SESSION['user'])) {
    header("Location: homepage.php");
    exit;
}

// Database connection
// Login processing logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = mysqli_connect("localhost", "root", "", "SA_PriSchool");
    if (!$conn) {
        die("DB connection failed.");
    }

    $user = mysqli_real_escape_string($conn, $_POST['username']);
    $pass = mysqli_real_escape_string($conn, $_POST['password']);

    // Use password_hash and password_verify for password encryption in actual application.
    $sql = "SELECT * FROM users WHERE username='$user' AND password='$pass'";
    $res = mysqli_query($conn, $sql);

    if (mysqli_num_rows($res) === 1) {
        $_SESSION['user'] = $user;
        header("Location: homepage.php");
        exit;
    } else {
        header("Location: index.php?error=Invalid username or password");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SA Primary School - Login</title>
    <link rel="stylesheet" href="index.css">
</head>
<body>
    <div class="login-box">
        <h2>Login</h2>
        <form action="index.php" method="POST">
        <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
                <br>
                <label for="password">Password</label>
                <input type="text" id="password" name="password" required>
                <br>
                <button type="submit">Log In</button>
        </form>
        <?php if (isset($_GET['error'])): ?>
            <p style="color:red; margin-top:10px;">⚠ <?= htmlspecialchars($_GET['error']) ?></p>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
// login handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = mysqli_connect("localhost", "root", "", "SA_PriSchool");
    if (!$conn) {
        die("DB connection failed.");
    }

    $user = mysqli_real_escape_string($conn, $_POST['username']);
    $pass = mysqli_real_escape_string($conn, $_POST['password']);

    $sql = "SELECT * FROM users WHERE username='$user' AND password='$pass'";
    $res = mysqli_query($conn, $sql);

    if (mysqli_num_rows($res) === 1) {
        $_SESSION['user'] = $user;
        header("Location: homepage.php");
        exit;
    } else {
        header("Location: index.php?error=Invalid username or password");
        exit;
    }
}
?>
