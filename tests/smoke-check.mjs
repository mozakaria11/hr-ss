import fs from 'node:fs';
import path from 'node:path';

const root = path.resolve(import.meta.dirname, '..');
const required = [
  'app/Bootstrap.php','public/index.php','public/forgot-password.php','public/reset-password.php','public/employee-photo.php','public/install.php','public/app.php',
  'public/api/state.php','public/api/index.php','public/api/users.php','public/api/employee-onboarding.php','public/api/employee-management.php','public/api/upload.php','public/api/hr.php','public/api/attendance.php','public/api/payroll.php','public/api/supply.php','public/api/procurement.php','public/api/operations.php','public/api/finance-tax.php','public/api/contracts.php','public/api/project-controls.php','public/api/settings.php',
  'public/upgrade.php','database/migrations/2026_07_20_002_core_hr.mysql.sql','database/migrations/2026_07_20_002_core_hr.sqlite.sql',
  'database/migrations/2026_07_20_003_attendance_payroll.mysql.sql','database/migrations/2026_07_20_003_attendance_payroll.sqlite.sql',
  'database/migrations/2026_07_20_004_supply_assets.mysql.sql','database/migrations/2026_07_20_004_supply_assets.sqlite.sql',
  'database/migrations/2026_07_20_005_contracts_revenue.mysql.sql','database/migrations/2026_07_20_005_contracts_revenue.sqlite.sql',
  'database/migrations/2026_07_20_006_project_controls.mysql.sql','database/migrations/2026_07_20_006_project_controls.sqlite.sql',
  'database/migrations/2026_07_20_007_procurement_match.mysql.sql','database/migrations/2026_07_20_007_procurement_match.sqlite.sql',
  'database/migrations/2026_07_20_008_operations_compliance.mysql.sql','database/migrations/2026_07_20_008_operations_compliance.sqlite.sql',
  'database/migrations/2026_07_20_009_finance_tax.mysql.sql','database/migrations/2026_07_20_009_finance_tax.sqlite.sql',
  'database/migrations/2026_07_20_010_employee_identity_access.mysql.sql','database/migrations/2026_07_20_010_employee_identity_access.sqlite.sql',
  'database/migrations/2026_07_21_011_employee_edit_permissions.mysql.sql','database/migrations/2026_07_21_011_employee_edit_permissions.sqlite.sql',
  'android/SawaedEmployeeWebView/app/src/main/java/com/sawaedarab/employee/MainActivity.java','android/SawaedEmployeeWebView/app/src/main/AndroidManifest.xml','android/SawaedEmployeeWebView/app/build.gradle',
  'database/schema.mysql.sql','database/schema.sqlite.sql','README.md','Dockerfile','docker-compose.yml'
];
let failed = false;
for (const file of required) {
  const full = path.join(root, file);
  if (!fs.existsSync(full) || fs.statSync(full).size === 0) { console.error('MISSING', file); failed = true; }
}
const app = fs.readFileSync(path.join(root,'public/app.php'),'utf8');
for (const marker of ['api/state.php','api/employee-onboarding.php','api/employee-management.php','api/attendance.php','api/payroll.php','api/supply.php','api/procurement.php','api/operations.php','api/finance-tax.php','api/contracts.php','api/project-controls.php','assets/backend-sync.js','App::requireAuth','submitEmployeeOnboarding','submitEmployeeEdit','uploadDocumentToServer','performAttendance','calculatePayroll','loadSupplyData','procurementAction','loadOperationsData','operationsAction','loadFinanceTaxData','financeTaxAction','loadContractsData','loadProjectControls']) {
  if (!app.includes(marker) && !fs.readFileSync(path.join(root,'public/assets/backend-sync.js'),'utf8').includes(marker)) { console.error('UNWIRED',marker); failed=true; }
}
if (failed) process.exit(1);
console.log(`OK: ${required.length} required files and frontend/backend wiring present.`);
