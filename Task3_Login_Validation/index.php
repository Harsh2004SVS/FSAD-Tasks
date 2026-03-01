<?php
$dbFile = __DIR__ . '/task3.db';
$pdo = new PDO('sqlite:' . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL,
        full_name TEXT NOT NULL,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )"
);

$existing = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
if ($existing === 0) {
    $stmt = $pdo->prepare('INSERT INTO users (email, password_hash, full_name) VALUES (:email, :password_hash, :full_name)');
    $stmt->execute([
        ':email' => 'admin@fsad.local',
        ':password_hash' => password_hash('Pass@123', PASSWORD_DEFAULT),
        ':full_name' => 'FSAD Admin',
    ]);
}

$errorMessage = '';
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $errorMessage = 'Password must be at least 6 characters.';
    } else {
        $stmt = $pdo->prepare('SELECT id, full_name, password_hash FROM users WHERE email = :email');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $errorMessage = 'Invalid email or password.';
        } else {
            $successMessage = 'Login successful. Welcome, ' . $user['full_name'] . '.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task 3 - Login Validation</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <main class="card">
        <h1>Login System</h1>
        <p class="hint">Demo credentials: <code>admin@fsad.local</code> / <code>Pass@123</code></p>

        <form id="loginForm" method="post" novalidate>
            <label for="email">Email</label>
            <input type="email" id="email" name="email" autocomplete="username" required>
            <small class="error" id="emailError"></small>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" autocomplete="current-password" required>
            <small class="error" id="passwordError"></small>

            <button type="submit">Login</button>
        </form>

        <div id="serverMessage" class="server-msg <?php echo $successMessage !== '' ? 'success' : 'error'; ?>">
            <?php
            if ($successMessage !== '') {
                echo htmlspecialchars($successMessage, ENT_QUOTES);
            } elseif ($errorMessage !== '') {
                echo htmlspecialchars($errorMessage, ENT_QUOTES);
            }
            ?>
        </div>
    </main>

    <script src="script.js"></script>
</body>
</html>
