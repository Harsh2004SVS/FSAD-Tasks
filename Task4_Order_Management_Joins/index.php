<?php
$dbFile = __DIR__ . '/task4.db';
$pdo = new PDO('sqlite:' . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS customers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        customer_name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE
    )"
);
$pdo->exec(
    "CREATE TABLE IF NOT EXISTS products (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        product_name TEXT NOT NULL,
        price REAL NOT NULL
    )"
);
$pdo->exec(
    "CREATE TABLE IF NOT EXISTS orders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        customer_id INTEGER NOT NULL,
        order_date TEXT NOT NULL,
        FOREIGN KEY (customer_id) REFERENCES customers(id)
    )"
);
$pdo->exec(
    "CREATE TABLE IF NOT EXISTS order_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_id INTEGER NOT NULL,
        product_id INTEGER NOT NULL,
        quantity INTEGER NOT NULL,
        unit_price REAL NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id),
        FOREIGN KEY (product_id) REFERENCES products(id)
    )"
);

if ((int) $pdo->query('SELECT COUNT(*) FROM customers')->fetchColumn() === 0) {
    $pdo->exec("INSERT INTO customers (customer_name, email) VALUES
        ('Isha Verma', 'isha@example.com'),
        ('Nikhil Rao', 'nikhil@example.com'),
        ('Priya Shah', 'priya@example.com')");

    $pdo->exec("INSERT INTO products (product_name, price) VALUES
        ('Laptop', 72000),
        ('Headphones', 2500),
        ('Keyboard', 1800),
        ('Mouse', 900)");

    $pdo->exec("INSERT INTO orders (customer_id, order_date) VALUES
        (1, '2025-01-10'),
        (2, '2025-01-15'),
        (1, '2025-02-01'),
        (3, '2025-02-11'),
        (2, '2025-02-21')");

    $pdo->exec("INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES
        (1, 1, 1, 72000),
        (1, 2, 2, 2500),
        (2, 3, 1, 1800),
        (2, 4, 1, 900),
        (3, 2, 1, 2500),
        (3, 3, 1, 1800),
        (4, 1, 1, 70000),
        (4, 4, 3, 900),
        (5, 2, 4, 2500)");
}

$customers = $pdo->query('SELECT id, customer_name FROM customers ORDER BY customer_name')->fetchAll(PDO::FETCH_ASSOC);
$selectedCustomer = $_GET['customer_id'] ?? 'all';

$where = '';
$params = [];
if ($selectedCustomer !== 'all' && ctype_digit($selectedCustomer)) {
    $where = ' WHERE c.id = :customer_id';
    $params[':customer_id'] = (int) $selectedCustomer;
}

$historySql =
    'SELECT o.id AS order_id, o.order_date, c.customer_name, p.product_name,
            oi.quantity, oi.unit_price,
            (oi.quantity * oi.unit_price) AS line_total
     FROM orders o
     JOIN customers c ON c.id = o.customer_id
     JOIN order_items oi ON oi.order_id = o.id
     JOIN products p ON p.id = oi.product_id' .
    $where .
    ' ORDER BY o.order_date DESC, o.id DESC';

$historyStmt = $pdo->prepare($historySql);
$historyStmt->execute($params);
$historyRows = $historyStmt->fetchAll(PDO::FETCH_ASSOC);

$highestOrder = $pdo->query(
    "SELECT order_id, customer_name, order_date, total_value
     FROM (
        SELECT o.id AS order_id, c.customer_name, o.order_date,
               SUM(oi.quantity * oi.unit_price) AS total_value
        FROM orders o
        JOIN customers c ON c.id = o.customer_id
        JOIN order_items oi ON oi.order_id = o.id
        GROUP BY o.id, c.customer_name, o.order_date
     ) order_totals
     ORDER BY total_value DESC
     LIMIT 1"
)->fetch(PDO::FETCH_ASSOC);

$mostActiveCustomer = $pdo->query(
    "SELECT c.customer_name, COUNT(o.id) AS order_count
     FROM customers c
     JOIN orders o ON o.customer_id = c.id
     GROUP BY c.id, c.customer_name
     ORDER BY order_count DESC, c.customer_name ASC
     LIMIT 1"
)->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task 4 - Order Management</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Order Management Dashboard</h1>
        <p>Customer order history using JOIN, plus subquery-based insights.</p>

        <form method="get" class="filter-row">
            <label for="customer_id">Filter by Customer</label>
            <select id="customer_id" name="customer_id">
                <option value="all">All Customers</option>
                <?php foreach ($customers as $customer): ?>
                    <option value="<?php echo (int) $customer['id']; ?>" <?php echo (string) $customer['id'] === $selectedCustomer ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($customer['customer_name'], ENT_QUOTES); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Apply</button>
        </form>

        <div class="insights">
            <div class="card">
                <h3>Highest Value Order</h3>
                <?php if ($highestOrder): ?>
                    <p>Order #<?php echo (int) $highestOrder['order_id']; ?></p>
                    <p><?php echo htmlspecialchars($highestOrder['customer_name'], ENT_QUOTES); ?></p>
                    <p>Date: <?php echo htmlspecialchars($highestOrder['order_date'], ENT_QUOTES); ?></p>
                    <p>Total: Rs. <?php echo number_format((float) $highestOrder['total_value'], 2); ?></p>
                <?php endif; ?>
            </div>
            <div class="card">
                <h3>Most Active Customer</h3>
                <?php if ($mostActiveCustomer): ?>
                    <p><?php echo htmlspecialchars($mostActiveCustomer['customer_name'], ENT_QUOTES); ?></p>
                    <p>Orders: <?php echo (int) $mostActiveCustomer['order_count']; ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Unit Price</th>
                        <th>Line Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($historyRows)): ?>
                        <tr><td colspan="7" class="empty">No order history found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($historyRows as $row): ?>
                            <tr>
                                <td><?php echo (int) $row['order_id']; ?></td>
                                <td><?php echo htmlspecialchars($row['order_date'], ENT_QUOTES); ?></td>
                                <td><?php echo htmlspecialchars($row['customer_name'], ENT_QUOTES); ?></td>
                                <td><?php echo htmlspecialchars($row['product_name'], ENT_QUOTES); ?></td>
                                <td><?php echo (int) $row['quantity']; ?></td>
                                <td>Rs. <?php echo number_format((float) $row['unit_price'], 2); ?></td>
                                <td>Rs. <?php echo number_format((float) $row['line_total'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
