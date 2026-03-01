<?php
$dbFile = __DIR__ . '/task5.db';
$pdo = new PDO('sqlite:' . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS accounts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        holder_name TEXT NOT NULL,
        account_type TEXT NOT NULL,
        balance REAL NOT NULL
    )"
);

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS payment_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        payer_id INTEGER NOT NULL,
        merchant_id INTEGER NOT NULL,
        amount REAL NOT NULL,
        status TEXT NOT NULL,
        note TEXT,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )"
);

if ((int) $pdo->query('SELECT COUNT(*) FROM accounts')->fetchColumn() === 0) {
    $pdo->exec("INSERT INTO accounts (holder_name, account_type, balance) VALUES
        ('Rahul User', 'USER', 5000),
        ('Anika User', 'USER', 3500),
        ('ShopKart', 'MERCHANT', 12000),
        ('FreshMart', 'MERCHANT', 8500)");
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payerId = (int) ($_POST['payer_id'] ?? 0);
    $merchantId = (int) ($_POST['merchant_id'] ?? 0);
    $amount = (float) ($_POST['amount'] ?? 0);
    $simulateFailure = isset($_POST['simulate_failure']);

    if ($payerId <= 0 || $merchantId <= 0 || $payerId === $merchantId || $amount <= 0) {
        $error = 'Please choose valid accounts and amount.';
    } else {
        try {
            $pdo->beginTransaction();

            $payerStmt = $pdo->prepare('SELECT id, holder_name, account_type, balance FROM accounts WHERE id = :id');
            $payerStmt->execute([':id' => $payerId]);
            $payer = $payerStmt->fetch(PDO::FETCH_ASSOC);

            $merchantStmt = $pdo->prepare('SELECT id, holder_name, account_type, balance FROM accounts WHERE id = :id');
            $merchantStmt->execute([':id' => $merchantId]);
            $merchant = $merchantStmt->fetch(PDO::FETCH_ASSOC);

            if (!$payer || !$merchant) {
                throw new RuntimeException('Account not found.');
            }
            if ($payer['account_type'] !== 'USER' || $merchant['account_type'] !== 'MERCHANT') {
                throw new RuntimeException('Invalid payer/merchant combination.');
            }
            if ((float) $payer['balance'] < $amount) {
                throw new RuntimeException('Insufficient balance. Transaction rolled back.');
            }

            $debitStmt = $pdo->prepare('UPDATE accounts SET balance = balance - :amount WHERE id = :id');
            $creditStmt = $pdo->prepare('UPDATE accounts SET balance = balance + :amount WHERE id = :id');

            $debitStmt->execute([':amount' => $amount, ':id' => $payerId]);
            $creditStmt->execute([':amount' => $amount, ':id' => $merchantId]);

            if ($simulateFailure) {
                throw new RuntimeException('Simulated failure triggered after updates.');
            }

            $logStmt = $pdo->prepare(
                'INSERT INTO payment_logs (payer_id, merchant_id, amount, status, note) VALUES (?, ?, ?, ?, ?)'
            );
            $logStmt->execute([$payerId, $merchantId, $amount, 'SUCCESS', 'COMMIT executed']);

            $pdo->commit();
            $message = 'Payment successful. COMMIT executed.';
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $logStmt = $pdo->prepare(
                'INSERT INTO payment_logs (payer_id, merchant_id, amount, status, note) VALUES (?, ?, ?, ?, ?)'
            );
            $logStmt->execute([$payerId, $merchantId, $amount, 'FAILED', 'ROLLBACK: ' . $e->getMessage()]);
            $error = $e->getMessage();
        }
    }
}

$users = $pdo->query("SELECT id, holder_name, balance FROM accounts WHERE account_type = 'USER' ORDER BY holder_name")
    ->fetchAll(PDO::FETCH_ASSOC);
$merchants = $pdo->query("SELECT id, holder_name, balance FROM accounts WHERE account_type = 'MERCHANT' ORDER BY holder_name")
    ->fetchAll(PDO::FETCH_ASSOC);
$allAccounts = $pdo->query('SELECT id, holder_name, account_type, balance FROM accounts ORDER BY account_type, holder_name')
    ->fetchAll(PDO::FETCH_ASSOC);
$logs = $pdo->query('SELECT * FROM payment_logs ORDER BY id DESC LIMIT 8')->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task 5 - Transaction Payment</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <main class="container">
        <h1>Transaction-Based Payment Simulation</h1>
        <p>Debit user, credit merchant, COMMIT on success and ROLLBACK on failure.</p>

        <?php if ($message !== ''): ?><div class="alert success"><?php echo htmlspecialchars($message, ENT_QUOTES); ?></div><?php endif; ?>
        <?php if ($error !== ''): ?><div class="alert error"><?php echo htmlspecialchars($error, ENT_QUOTES); ?></div><?php endif; ?>

        <form method="post" class="grid">
            <div>
                <label for="payer_id">Payer (User)</label>
                <select name="payer_id" id="payer_id" required>
                    <option value="">Select User</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo (int) $user['id']; ?>">
                            <?php echo htmlspecialchars($user['holder_name'], ENT_QUOTES); ?> (Rs. <?php echo number_format((float) $user['balance'], 2); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="merchant_id">Merchant</label>
                <select name="merchant_id" id="merchant_id" required>
                    <option value="">Select Merchant</option>
                    <?php foreach ($merchants as $merchant): ?>
                        <option value="<?php echo (int) $merchant['id']; ?>">
                            <?php echo htmlspecialchars($merchant['holder_name'], ENT_QUOTES); ?> (Rs. <?php echo number_format((float) $merchant['balance'], 2); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="amount">Amount</label>
                <input type="number" id="amount" name="amount" step="0.01" min="1" required>
            </div>

            <div class="checkbox-row">
                <label><input type="checkbox" name="simulate_failure"> Simulate failure to test ROLLBACK</label>
            </div>

            <button type="submit">Process Payment</button>
        </form>

        <section>
            <h2>Account Balances</h2>
            <table>
                <thead><tr><th>ID</th><th>Name</th><th>Type</th><th>Balance</th></tr></thead>
                <tbody>
                    <?php foreach ($allAccounts as $account): ?>
                        <tr>
                            <td><?php echo (int) $account['id']; ?></td>
                            <td><?php echo htmlspecialchars($account['holder_name'], ENT_QUOTES); ?></td>
                            <td><?php echo htmlspecialchars($account['account_type'], ENT_QUOTES); ?></td>
                            <td>Rs. <?php echo number_format((float) $account['balance'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section>
            <h2>Recent Payment Logs</h2>
            <table>
                <thead><tr><th>ID</th><th>Payer</th><th>Merchant</th><th>Amount</th><th>Status</th><th>Note</th><th>Time</th></tr></thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo (int) $log['id']; ?></td>
                            <td><?php echo (int) $log['payer_id']; ?></td>
                            <td><?php echo (int) $log['merchant_id']; ?></td>
                            <td>Rs. <?php echo number_format((float) $log['amount'], 2); ?></td>
                            <td><?php echo htmlspecialchars($log['status'], ENT_QUOTES); ?></td>
                            <td><?php echo htmlspecialchars($log['note'], ENT_QUOTES); ?></td>
                            <td><?php echo htmlspecialchars($log['created_at'], ENT_QUOTES); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </main>
</body>
</html>
