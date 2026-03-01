<?php
$dbFile = __DIR__ . '/task2.db';
$pdo = new PDO('sqlite:' . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS students (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        department TEXT NOT NULL,
        admission_date TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE
    )"
);

$count = (int) $pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();
if ($count === 0) {
    $seed = [
        ['Aarav Sharma', 'CSE', '2024-07-10', 'aarav@college.edu'],
        ['Bhavya Reddy', 'ECE', '2024-06-20', 'bhavya@college.edu'],
        ['Charan Kumar', 'CSE', '2024-08-01', 'charan@college.edu'],
        ['Diya Patel', 'EEE', '2024-04-15', 'diya@college.edu'],
        ['Eshan Rao', 'MECH', '2024-05-19', 'eshan@college.edu'],
        ['Farah Ali', 'CIVIL', '2024-03-05', 'farah@college.edu'],
        ['Gauri Nair', 'ECE', '2024-02-12', 'gauri@college.edu'],
    ];

    $stmt = $pdo->prepare('INSERT INTO students (name, department, admission_date, email) VALUES (?, ?, ?, ?)');
    foreach ($seed as $row) {
        $stmt->execute($row);
    }
}

$sort = $_GET['sort'] ?? 'name_asc';
$department = $_GET['department'] ?? 'all';

$sortOptions = [
    'name_asc' => 'name ASC',
    'name_desc' => 'name DESC',
    'date_asc' => 'admission_date ASC',
    'date_desc' => 'admission_date DESC',
];
$orderBy = $sortOptions[$sort] ?? $sortOptions['name_asc'];

$departments = $pdo->query('SELECT DISTINCT department FROM students ORDER BY department')->fetchAll(PDO::FETCH_COLUMN);

$whereSql = '';
$params = [];
if ($department !== 'all' && in_array($department, $departments, true)) {
    $whereSql = ' WHERE department = :department';
    $params[':department'] = $department;
}

$sql = 'SELECT id, name, department, admission_date, email FROM students' . $whereSql . ' ORDER BY ' . $orderBy;
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

$counts = $pdo->query(
    'SELECT department, COUNT(*) AS total_students FROM students GROUP BY department ORDER BY department'
)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task 2 - Data Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <main class="layout">
        <section class="panel">
            <h1>Student Records Dashboard</h1>
            <p>Sort by name/date, filter by department, and view department totals.</p>

            <form class="filters" method="get">
                <div>
                    <label for="sort">Sort By</label>
                    <select name="sort" id="sort">
                        <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Name (A-Z)</option>
                        <option value="name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Name (Z-A)</option>
                        <option value="date_asc" <?php echo $sort === 'date_asc' ? 'selected' : ''; ?>>Date (Oldest)</option>
                        <option value="date_desc" <?php echo $sort === 'date_desc' ? 'selected' : ''; ?>>Date (Newest)</option>
                    </select>
                </div>

                <div>
                    <label for="department">Department</label>
                    <select name="department" id="department">
                        <option value="all">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept, ENT_QUOTES); ?>" <?php echo $department === $dept ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept, ENT_QUOTES); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit">Apply</button>
            </form>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Admission Date</th>
                            <th>Email</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($records)): ?>
                            <tr><td colspan="5" class="empty">No records found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($records as $record): ?>
                                <tr>
                                    <td><?php echo (int) $record['id']; ?></td>
                                    <td><?php echo htmlspecialchars($record['name'], ENT_QUOTES); ?></td>
                                    <td><?php echo htmlspecialchars($record['department'], ENT_QUOTES); ?></td>
                                    <td><?php echo htmlspecialchars($record['admission_date'], ENT_QUOTES); ?></td>
                                    <td><?php echo htmlspecialchars($record['email'], ENT_QUOTES); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <aside class="panel side">
            <h2>Count Per Department</h2>
            <ul>
                <?php foreach ($counts as $entry): ?>
                    <li>
                        <span><?php echo htmlspecialchars($entry['department'], ENT_QUOTES); ?></span>
                        <strong><?php echo (int) $entry['total_students']; ?></strong>
                    </li>
                <?php endforeach; ?>
            </ul>
        </aside>
    </main>
</body>
</html>
