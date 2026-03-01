CREATE TABLE students (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    department TEXT NOT NULL,
    admission_date TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE
);

-- Sorting and filtering
SELECT id, name, department, admission_date, email
FROM students
WHERE department = 'CSE'
ORDER BY name ASC;

-- Count of students per department
SELECT department, COUNT(*) AS total_students
FROM students
GROUP BY department
ORDER BY department;
