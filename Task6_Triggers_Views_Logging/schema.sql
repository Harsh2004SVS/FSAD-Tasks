CREATE TABLE employees (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_name TEXT NOT NULL,
    department TEXT NOT NULL,
    salary REAL NOT NULL,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE activity_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id INTEGER NOT NULL,
    operation TEXT NOT NULL,
    old_salary REAL,
    new_salary REAL,
    changed_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TRIGGER trg_employees_insert
AFTER INSERT ON employees
BEGIN
    INSERT INTO activity_log (employee_id, operation, old_salary, new_salary, changed_at)
    VALUES (NEW.id, 'INSERT', NULL, NEW.salary, datetime('now'));
END;

CREATE TRIGGER trg_employees_update
AFTER UPDATE ON employees
BEGIN
    INSERT INTO activity_log (employee_id, operation, old_salary, new_salary, changed_at)
    VALUES (NEW.id, 'UPDATE', OLD.salary, NEW.salary, datetime('now'));
END;

CREATE VIEW daily_activity_report AS
SELECT date(changed_at) AS activity_date,
       operation,
       COUNT(*) AS total_actions
FROM activity_log
GROUP BY date(changed_at), operation
ORDER BY activity_date DESC, operation;
