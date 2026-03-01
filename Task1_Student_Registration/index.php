<?php
$dbFile = __DIR__ . '/task1.db';
$pdo = new PDO('sqlite:' . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS students (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        dob TEXT NOT NULL,
        department TEXT NOT NULL,
        phone TEXT NOT NULL,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )"
);

$message = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $dob = trim($_POST['dob'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($name === '' || strlen($name) < 2) {
        $errors[] = 'Please enter a valid name.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($dob === '') {
        $errors[] = 'Date of birth is required.';
    }
    if ($department === '') {
        $errors[] = 'Please choose a department.';
    }
    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        $errors[] = 'Phone number must be exactly 10 digits.';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO students (name, email, dob, department, phone) VALUES (:name, :email, :dob, :department, :phone)'
            );
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':dob' => $dob,
                ':department' => $department,
                ':phone' => $phone,
            ]);
            $message = 'Student registered successfully.';
        } catch (PDOException $e) {
            if ((int) $e->getCode() === 23000) {
                $errors[] = 'This email is already registered.';
            } else {
                $errors[] = 'Unable to save data right now.';
            }
        }
    }
}

$students = $pdo->query(
    'SELECT id, name, email, dob, department, phone, created_at FROM students ORDER BY id DESC'
)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task 1 - Student Registration</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Student Registration Form</h1>
        <p class="subtitle">Collect and store student records in a database</p>

        <?php if ($message !== ''): ?>
            <div class="alert success"><?php echo htmlspecialchars($message, ENT_QUOTES); ?></div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error, ENT_QUOTES); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" novalidate>
            <label for="name">Name</label>
            <input type="text" id="name" name="name" required minlength="2" placeholder="Enter full name">

            <label for="email">Email</label>
            <input type="email" id="email" name="email" required placeholder="name@example.com">

            <label for="dob">Date of Birth</label>
            <input type="date" id="dob" name="dob" required>

            <label for="department">Department</label>
            <select id="department" name="department" required>
                <option value="">Select Department</option>
                <option value="CSE">CSE</option>
                <option value="ECE">ECE</option>
                <option value="EEE">EEE</option>
                <option value="MECH">MECH</option>
                <option value="CIVIL">CIVIL</option>
            </select>

            <label for="phone">Phone</label>
            <input type="tel" id="phone" name="phone" required pattern="[0-9]{10}" maxlength="10" placeholder="10 digit number">

            <button type="submit">Register Student</button>
        </form>

        <h2>Registered Students (SELECT Result)</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>DOB</th>
                        <th>Department</th>
                        <th>Phone</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="7" class="no-data">No records found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo (int) $student['id']; ?></td>
                                <td><?php echo htmlspecialchars($student['name'], ENT_QUOTES); ?></td>
                                <td><?php echo htmlspecialchars($student['email'], ENT_QUOTES); ?></td>
                                <td><?php echo htmlspecialchars($student['dob'], ENT_QUOTES); ?></td>
                                <td><?php echo htmlspecialchars($student['department'], ENT_QUOTES); ?></td>
                                <td><?php echo htmlspecialchars($student['phone'], ENT_QUOTES); ?></td>
                                <td><?php echo htmlspecialchars($student['created_at'], ENT_QUOTES); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
