#!/usr/bin/env python3
import glob
import sqlite3
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
db = sqlite3.connect(":memory:")
db.execute("PRAGMA foreign_keys=ON")
db.executescript((ROOT / "database/schema.sqlite.sql").read_text())
for migration in sorted(glob.glob(str(ROOT / "database/migrations/*.sqlite.sql"))):
    db.executescript(Path(migration).read_text())

expected = {"hr.employee.create", "hr.employee.update", "hr.employee.account.manage"}
found = {row[0] for row in db.execute("SELECT permission_key FROM permissions WHERE permission_key LIKE 'hr.employee.%'")}
assert expected <= found, f"missing employee permissions: {expected - found}"

for role in ("general-manager", "executive-director", "hr-manager"):
    granted = {row[0] for row in db.execute(
        "SELECT p.permission_key FROM permissions p JOIN role_permissions rp ON rp.permission_id=p.id JOIN roles r ON r.id=rp.role_id WHERE r.role_key=?",
        (role,),
    )}
    assert expected <= granted, f"{role} lacks: {expected - granted}"

worker_grants = {row[0] for row in db.execute(
    "SELECT p.permission_key FROM permissions p JOIN role_permissions rp ON rp.permission_id=p.id JOIN roles r ON r.id=rp.role_id WHERE r.role_key='worker'"
)}
assert not (expected & worker_grants), "worker unexpectedly received employee administration permissions"

db.execute("INSERT INTO companies(code,name,status) VALUES('SAC','سواعد','active')")
company_id = db.execute("SELECT id FROM companies WHERE code='SAC'").fetchone()[0]
db.execute("INSERT INTO projects(company_id,code,name,status) VALUES(?,?,?,?)", (company_id, "P-1", "مشروع اختبار", "active"))
project_id = db.execute("SELECT id FROM projects WHERE code='P-1'").fetchone()[0]
db.execute("INSERT INTO users(name,email,password_hash,role,is_active) VALUES(?,?,?,?,1)", ("موظف قديم", "old@example.com", "hash", "worker"))
user_id = db.execute("SELECT id FROM users WHERE email='old@example.com'").fetchone()[0]
db.execute("INSERT INTO employees(company_id,project_id,emp_code,name,job_title,base_salary,status,national_id,work_email,employee_type) VALUES(?,?,?,?,?,?,?,?,?,?)", (company_id, project_id, "EMP-1", "موظف قديم", "عامل", 3000, "active", "1000000001", "old@example.com", "direct"))
employee_id = db.execute("SELECT id FROM employees WHERE emp_code='EMP-1'").fetchone()[0]
db.execute("INSERT INTO user_employee_links(user_id,employee_id) VALUES(?,?)", (user_id, employee_id))
db.execute("INSERT INTO employee_contracts(employee_id,contract_type,starts_on,basic_salary,status) VALUES(?,?,?,?,?)", (employee_id, "full-time", "2026-01-01", 3000, "active"))

db.execute("UPDATE employees SET name=?,work_email=?,base_salary=? WHERE id=?", ("موظف محدث", "new@example.com", 4200, employee_id))
db.execute("UPDATE users SET name=?,email=? WHERE id=?", ("موظف محدث", "new@example.com", user_id))
db.execute("UPDATE employee_contracts SET basic_salary=? WHERE employee_id=? AND status='active'", (4200, employee_id))
assert db.execute("SELECT name,work_email,base_salary FROM employees WHERE id=?", (employee_id,)).fetchone() == ("موظف محدث", "new@example.com", 4200.0)
assert db.execute("SELECT name,email FROM users WHERE id=?", (user_id,)).fetchone() == ("موظف محدث", "new@example.com")
assert db.execute("SELECT basic_salary FROM employee_contracts WHERE employee_id=?", (employee_id,)).fetchone()[0] == 4200.0

print("OK: employee permissions, role isolation, and linked employee/account/contract update verified")
