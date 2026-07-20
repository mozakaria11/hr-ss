INSERT OR IGNORE INTO permissions(permission_key,name_ar,module_name) VALUES
('hr.employee.create','إضافة موظف وحساب دخول','hr'),
('hr.employee.update','تعديل بيانات الموظفين','hr'),
('hr.employee.account.manage','إدارة حسابات دخول الموظفين','hr');

INSERT OR IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r CROSS JOIN permissions p
WHERE r.role_key='general-manager' AND p.permission_key IN ('hr.employee.create','hr.employee.update','hr.employee.account.manage');

INSERT OR IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r CROSS JOIN permissions p
WHERE r.role_key IN ('executive-director','hr-manager') AND p.permission_key IN ('hr.employee.create','hr.employee.update','hr.employee.account.manage');
