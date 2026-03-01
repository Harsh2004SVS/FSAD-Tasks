<?php
$dbFile = __DIR__ . '/task6.db';
$pdo = new PDO('sqlite:' . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS employees (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        employee_name TEXT NOT NULL,
        department TEXT NOT NULL,
        salary REAL NOT NULL,
        updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )"
);

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS activity_log (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        employee_id INTEGER NOT NULL,
        operation TEXT NOT NULL,
        old_salary REAL,
        new_salary REAL,
        changed_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (employee_id) REFERENCES employees(id)
    )"
);

$pdo->exec('DROP TRIGGER IF EXISTS trg_employees_insert');
$pdo->exec('DROP TRIGGER IF EXISTS trg_employees_update');

$pdo->exec(
    "CREATE TRIGGER trg_employees_insert
    AFTER INSERT ON employees
    BEGIN
        INSERT INTO activity_log (employee_id, operation, old_salary, new_salary, changed_at)
        VALUES (NEW.id, 'INSERT', NULL, NEW.salary, datetime('now'));
    END"
);

$pdo->exec(
    "CREATE TRIGGER trg_employees_update
    AFTER UPDATE ON employees
    BEGIN
        INSERT INTO activity_log (employee_id, operation, old_salary, new_salary, changed_at)
        VALUES (NEW.id, 'UPDATE', OLD.salary, NEW.salary, datetime('now'));
    END"
);

$pdo->exec('DROP VIEW IF EXISTS daily_activity_report');
$pdo->exec(
    "CREATE VIEW daily_activity_report AS
     SELECT date(changed_at) AS activity_date,
            operation,
            COUNT(*) AS total_actions
     FROM activity_log
     GROUP BY date(changed_at), operation
     ORDER BY activity_date DESC, operation"
);

if ((int) $pdo->query('SELECT COUNT(*) FROM employees')->fetchColumn() === 0) {
    $seed = $pdo->prepare('INSERT INTO employees (employee_name, department, salary) VALUES (?, ?, ?)');
    $seed->execute(['Ajay Kumar', 'IT', 45000]);
    $seed->execute(['Neha Singh', 'HR', 42000]);
    $seed->execute(['Ritu Das', 'Finance', 47000]);
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'add') {
        $name = trim($_POST['employee_name'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $salary = (float) ($_POST['salary'] ?? 0);

        if ($name !== '' && $department !== '' && $salary > 0) {
            $stmt = $pdo->prepare('INSERT INTO employees (employee_name, department, salary) VALUES (?, ?, ?)');
            $stmt->execute([$name, $department, $salary]);
            $message = 'Employee inserted. Trigger logged INSERT.';
        }
    }

    if (($_POST['action'] ?? '') === 'update') {
        $employeeId = (int) ($_POST['employee_id'] ?? 0);
        $newSalary = (float) ($_POST['new_salary'] ?? 0);

        if ($employeeId > 0 && $newSalary > 0) {
            $stmt = $pdo->prepare('UPDATE employees SET salary = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
            $stmt->execute([$newSalary, $employeeId]);
            $message = 'Employee updated. Trigger logged UPDATE.';
        }
    }
}

$employees = $pdo->query('SELECT * FROM employees ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
$logs = $pdo->query('SELECT * FROM activity_log ORDER BY id DESC LIMIT 12')->fetchAll(PDO::FETCH_ASSOC);
$report = $pdo->query('SELECT * FROM daily_activity_report')->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task 6 - Triggers & Views</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Automated Logging with Triggers & Views</h1>
        <?php if ($message !== ''): ?><p class="msg"><?php echo htmlspecialchars($message, ENT_QUOTES); ?></p><?php endif; ?>

        <section class="forms">
            <form method="post">
                <h2>Insert Employee</h2>
                <input type="hidden" name="action" value="add">
                <label>Name</label>
                <input type="text" name="employee_name" required>
                <label>Department</label>
                <input type="text" name="department" required>
                <label>Salary</label>
                <input type="number" name="salary" step="0.01" min="1" required>
                <button type="submit">Insert</button>
            </form>

            <form method="post">
                <h2>Update Salary</h2>
                <input type="hidden" name="action" value="update">
                <label>Employee</label>
                <select name="employee_id" required>
                    <option value="">Select Employee</option>
                    <?php foreach ($employees as $employee): ?>
                        <option value="<?php echo (int) $employee['id']; ?>">
                            <?php echo htmlspecialchars($employee['employee_name'], ENT_QUOTES); ?> (Current: Rs. <?php echo number_format((float) $employee['salary'], 2); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <label>New Salary</label>
                <input type="number" name="new_salary" step="0.01" min="1" required>
                <button type="submit">Update</button>
            </form>
        </section>

        <section>
            <h2>Employees</h2>
            <table>
                <thead><tr><th>ID</th><th>Name</th><th>Department</th><th>Salary</th><th>Updated At</th></tr></thead>
                <tbody>
                    <?php foreach ($employees as $employee): ?>
                        <tr>
                            <td><?php echo (int) $employee['id']; ?></td>
                            <td><?php echo htmlspecialchars($employee['employee_name'], ENT_QUOTES); ?></td>
                            <td><?php echo htmlspecialchars($employee['department'], ENT_QUOTES); ?></td>
                            <td>Rs. <?php echo number_format((float) $employee['salary'], 2); ?></td>
                            <td><?php echo htmlspecialchars($employee['updated_at'], ENT_QUOTES); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section>
            <h2>Activity Log (Trigger Output)</h2>
            <table>
                <thead><tr><th>ID</th><th>Employee ID</th><th>Operation</th><th>Old Salary</th><th>New Salary</th><th>Changed At</th></tr></thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo (int) $log['id']; ?></td>
                            <td><?php echo (int) $log['employee_id']; ?></td>
                            <td><?php echo htmlspecialchars($log['operation'], ENT_QUOTES); ?></td>
                            <td><?php echo $log['old_salary'] !== null ? 'Rs. ' . number_format((float) $log['old_salary'], 2) : '-'; ?></td>
                            <td>Rs. <?php echo number_format((float) $log['new_salary'], 2); ?></td>
                            <td><?php echo htmlspecialchars($log['changed_at'], ENT_QUOTES); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section>
            <h2>Daily Activity Report (View)</h2>
            <table>
                <thead><tr><th>Date</th><th>Operation</th><th>Total Actions</th></tr></thead>
                <tbody>
                    <?php if (empty($report)): ?>
                        <tr><td colspan="3">No activity yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($report as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['activity_date'], ENT_QUOTES); ?></td>
                                <td><?php echo htmlspecialchars($row['operation'], ENT_QUOTES); ?></td>
                                <td><?php echo (int) $row['total_actions']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </div>
</body>
</html>
