CREATE TABLE accounts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    holder_name TEXT NOT NULL,
    account_type TEXT NOT NULL,
    balance REAL NOT NULL
);

CREATE TABLE payment_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    payer_id INTEGER NOT NULL,
    merchant_id INTEGER NOT NULL,
    amount REAL NOT NULL,
    status TEXT NOT NULL,
    note TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Transaction simulation
BEGIN TRANSACTION;
UPDATE accounts SET balance = balance - 500 WHERE id = 1;
UPDATE accounts SET balance = balance + 500 WHERE id = 3;
COMMIT;

-- On failure:
-- ROLLBACK;
