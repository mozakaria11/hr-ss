/* ربط واجهة النموذج بقاعدة بيانات الخادم — بدون أي مكتبات خارجية. */
const SERVER_USER = JSON.parse(document.querySelector('meta[name="app-user"]').content);
const SERVER_CSRF = document.querySelector('meta[name="csrf-token"]').content;
const CAN_SAVE_STATE = ['general-manager','executive-director','projects-director','project-manager','hr-manager'].includes(SERVER_USER.role);
let serverSaveTimer = null;
let serverSaving = false;

function setServerStatus(text, tone = 'ok') {
  const el = document.getElementById('serverSaveStatus');
  if (!el) return;
  el.textContent = tone === 'error' ? '● تعذر الحفظ' : tone === 'saving' ? '● جارٍ الحفظ' : '● ' + text;
  el.style.color = tone === 'error' ? '#ff7777' : tone === 'saving' ? '#e3b855' : '';
}

function currentServerState() {
  return { employees, projects, journalEntries, suppliers, equipment, materials, purchaseOrders, geofences };
}

async function persistCoreState() {
  if (serverSaving) return scheduleServerSave();
  serverSaving = true;
  setServerStatus('جارٍ الحفظ', 'saving');
  try {
    const response = await fetch('api/state.php', {
      method: 'PUT',
      headers: {'Content-Type':'application/json','X-CSRF-Token':SERVER_CSRF,'Accept':'application/json'},
      body: JSON.stringify({state: currentServerState()})
    });
    if (response.status === 401) return location.href = 'index.php';
    const result = await response.json();
    if (!response.ok || !result.ok) throw new Error(result.message || 'تعذر الحفظ');
    setServerStatus('محفوظ');
  } catch (error) {
    setServerStatus('تعذر الحفظ', 'error');
    console.error(error);
  } finally {
    serverSaving = false;
  }
}

function scheduleServerSave() {
  if (!CAN_SAVE_STATE) return;
  clearTimeout(serverSaveTimer);
  serverSaveTimer = setTimeout(persistCoreState, 500);
}

function replaceArray(target, source) {
  if (Array.isArray(source)) target.splice(0, target.length, ...source);
}

async function hydrateFromServer() {
  try {
    setServerStatus('مزامنة', 'saving');
    const response = await fetch('api/state.php', {headers:{'Accept':'application/json'}});
    if (response.status === 401) return location.href = 'index.php';
    const result = await response.json();
    if (!result.ok) throw new Error(result.message || 'فشلت المزامنة');
    if (result.data) {
      replaceArray(employees, result.data.employees);
      replaceArray(projects, result.data.projects);
      replaceArray(journalEntries, result.data.journalEntries);
      replaceArray(suppliers, result.data.suppliers);
      replaceArray(equipment, result.data.equipment);
      replaceArray(materials, result.data.materials);
      replaceArray(purchaseOrders, result.data.purchaseOrders);
      replaceArray(geofences, result.data.geofences);
    } else {
      await persistCoreState();
    }
    if (ROLES[SERVER_USER.role]) {
      currentRole = SERVER_USER.role;
      const roleSelect = document.getElementById('roleSelect');
      roleSelect.value = currentRole;
      if (SERVER_USER.role !== 'general-manager') roleSelect.disabled = true;
    }
    if (['general-manager','executive-director'].includes(SERVER_USER.role)) await hydrateSecuritySettings();
    go(currentView);
    setServerStatus('محفوظ');
  } catch (error) {
    setServerStatus('تعذر الحفظ', 'error');
    toast('تعذر تحميل بيانات الخادم. تحقق من الاتصال.', 'danger');
    console.error(error);
  }
}

async function serverApi(url, options = {}) {
  const response = await fetch(url, {headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-Token':SERVER_CSRF,...(options.headers||{})},...options});
  if (response.status === 401) location.href='index.php';
  const result = await response.json();
  if (!response.ok || !result.ok) throw new Error(result.message || 'تعذر تنفيذ العملية');
  return result;
}

async function hydrateSecuritySettings() {
  try {
    const result = await serverApi('api/settings.php');
    if (result.data.matrix) Object.keys(result.data.matrix).forEach(role => permMatrix[role] = result.data.matrix[role]);
    if (result.data.thresholds) Object.keys(result.data.thresholds).forEach(role => thresholds[role] = {...(thresholds[role]||{}),...result.data.thresholds[role]});
    if (result.data.employeeRoles) Object.assign(employeeRoles,result.data.employeeRoles);
  } catch (error) { console.warn('Security settings not loaded',error); }
}

async function persistPermission(role, permission, granted) {
  try { await serverApi('api/settings.php',{method:'PUT',body:JSON.stringify({action:'permission',role,permission,granted})}); setServerStatus('محفوظ'); }
  catch(error){ toast(error.message,'danger'); setServerStatus('تعذر الحفظ','error'); }
}

async function persistThreshold(role, type, amount) {
  try { await serverApi('api/settings.php',{method:'PUT',body:JSON.stringify({action:'threshold',role,type,amount})}); setServerStatus('محفوظ'); }
  catch(error){ toast(error.message,'danger'); setServerStatus('تعذر الحفظ','error'); }
}

async function persistEmployeeRole(empCode, role) {
  try { await serverApi('api/settings.php',{method:'PUT',body:JSON.stringify({action:'employee_role',emp_code:empCode,role})}); setServerStatus('محفوظ'); }
  catch(error){ toast(error.message,'danger'); setServerStatus('تعذر الحفظ','error'); }
}

function openNewSystemUserModal(){
  openFormModal('إنشاء حساب دخول لموظف',[
    fmField('الموظف','emp_code','select',employees.map(e=>`<option value="${e.id}">${e.name} (${e.id})</option>`).join('')),
    fmField('رقم الهوية / الإقامة (10 أرقام)','national_id'),fmField('اسم المستخدم','name'),fmField('البريد الإلكتروني واستعادة الحساب','email','email'),fmField('كلمة المرور المؤقتة','password','password'),
    fmField('الدور','role','select',roleOrder.map(r=>`<option value="${r}">${ROLES[r].label}</option>`).join(''))
  ].join(''),'إنشاء الحساب',async v=>{
    try{await serverApi('api/users.php',{method:'POST',body:JSON.stringify(v)});toast('✓ تم إنشاء حساب الموظف وربطه بملفه');}
    catch(error){toast(error.message,'danger');}
  });
}

async function submitEmployeeOnboarding(event){
  event.preventDefault();
  const form=event.currentTarget;
  if(!form.reportValidity())return;
  const button=document.getElementById('employeeOnboardingSubmit');
  button.disabled=true;button.textContent='جارٍ إنشاء الموظف والحساب...';
  try{
    const response=await fetch('api/employee-onboarding.php',{method:'POST',headers:{'Accept':'application/json','X-CSRF-Token':SERVER_CSRF},body:new FormData(form)});
    if(response.status===401){location.href='index.php';return;}
    const result=await response.json();
    if(!response.ok||!result.ok)throw new Error(result.message||'تعذر إنشاء الموظف');
    closeFormModal();
    toast('✓ '+result.message+' — '+result.data.emp_code);
    await hydrateFromServer();
    if(currentView==='employees')go('employees');
  }catch(error){toast(error.message,'danger');button.disabled=false;button.textContent='إضافة الموظف وإنشاء الحساب';}
}

async function submitEmployeeEdit(event){
  event.preventDefault();const form=event.currentTarget;if(!form.reportValidity())return;
  const button=document.getElementById('employeeEditSubmit');button.disabled=true;button.textContent='جارٍ الحفظ...';
  try{
    const payload=Object.fromEntries(new FormData(form).entries());
    const result=await serverApi('api/employee-management.php',{method:'PATCH',body:JSON.stringify(payload)});
    closeFormModal();toast('✓ '+result.message);employeeDetails[payload.emp_code]=undefined;await hydrateFromServer();openEmployeeFile(payload.emp_code);
  }catch(error){toast(error.message,'danger');button.disabled=false;button.textContent='حفظ التعديلات';}
}

async function loadEmployeeDetails(empCode) {
  try {
    const result = await serverApi('api/hr.php?emp_code='+encodeURIComponent(empCode));
    employeeDetails[empCode] = result.data;
    if (empFileId === empCode) renderEmployeeFile();
  } catch(error) { toast(error.message,'danger'); }
}

function openEmployeeContractModal(empCode) {
  const current = employeeDetails[empCode]?.contract || {};
  openFormModal('عقد الموظف',[
    fmField('نوع العقد','contract_type','select',fmOptions(['غير محدد المدة','محدد المدة','مؤقت'])),
    fmField('تاريخ البداية','starts_on','date',current.starts_on||''),fmField('تاريخ النهاية','ends_on','date',current.ends_on||''),
    fmField('نهاية فترة التجربة','probation_ends_on','date',current.probation_ends_on||''),fmField('الساعات الأسبوعية','weekly_hours','number',current.weekly_hours||48),
    fmField('الراتب الأساسي','basic_salary','number',current.basic_salary||0),fmField('بدل السكن','housing_allowance','number',current.housing_allowance||0),
    fmField('بدل النقل','transport_allowance','number',current.transport_allowance||0),fmField('بدلات أخرى','other_allowances','number',current.other_allowances||0)
  ].join(''),'حفظ العقد',async v=>{
    try { await serverApi('api/hr.php',{method:'POST',body:JSON.stringify({action:'contract',emp_code:empCode,...v})}); toast('✓ تم حفظ عقد الموظف'); await loadEmployeeDetails(empCode); }
    catch(error){toast(error.message,'danger');}
  });
  document.querySelector('[data-field="contract_type"]').value=current.contract_type||'غير محدد المدة';
  document.querySelector('[data-field="ends_on"]').dataset.optional='1';
  document.querySelector('[data-field="probation_ends_on"]').dataset.optional='1';
}

function openLeaveRequestModal(empCode) {
  openFormModal('طلب إجازة',[
    fmField('نوع الإجازة','leave_type','select','<option value="annual">سنوية</option><option value="emergency">اضطرارية</option><option value="sick">مرضية</option><option value="unpaid">بدون راتب</option>'),
    fmField('من تاريخ','starts_on','date'),fmField('إلى تاريخ','ends_on','date'),fmField('السبب','reason','textarea')
  ].join(''),'إرسال للاعتماد',async v=>{
    try { await serverApi('api/hr.php',{method:'POST',body:JSON.stringify({action:'leave',emp_code:empCode,...v})}); toast('✓ تم إرسال طلب الإجازة للاعتماد'); await loadEmployeeDetails(empCode); }
    catch(error){toast(error.message,'danger');}
  });
  document.querySelector('[data-field="reason"]').dataset.optional='1';
}

async function requestEmployeeTransfer(empCode) {
  const to=document.getElementById('transferProject')?.value,date=document.getElementById('transferDate')?.value,percent=document.getElementById('transferPercent')?.value;
  if(!to||!date) return toast('اختر المشروع وتاريخ السريان','danger');
  try { await serverApi('api/hr.php',{method:'POST',body:JSON.stringify({action:'transfer',emp_code:empCode,to_project_code:to,effective_on:date,cost_allocation_percent:Number(percent)||100})}); toast('✓ تم إرسال طلب النقل للاعتماد'); await loadEmployeeDetails(empCode); }
  catch(error){toast(error.message,'danger');}
}

async function loadHrApprovals() {
  try {
    const result=await serverApi('api/hr.php?pending=1');
    const rows=result.data.map(r=>({
      t:(r.request_kind==='leave'?'طلب إجازة — ':'طلب نقل إلى مشروع — ')+r.request_name,
      u:r.employee_name+' ('+r.emp_code+')',v:r.days_count?r.days_count+' يوم':'—',pr:badge('بانتظار الاعتماد','warn'),
      act:`<button class="btn-sm btn" onclick="event.stopPropagation();approveHrRequest('${r.request_kind}',${r.id},'approved')">اعتماد</button> <button class="btn-sm btn-outline" onclick="event.stopPropagation();approveHrRequest('${r.request_kind}',${r.id},'rejected')">رفض</button>`
    }));
    approvalsData.pending=rows;
    if(currentView==='approvals'&&approvalsTab==='pending') document.getElementById('approvalsContent').innerHTML=approvalsTabContent();
  } catch(error){console.warn(error);}
}

async function approveHrRequest(kind,id,decision){
  try{
    await serverApi('api/hr.php',{method:'POST',body:JSON.stringify({action:kind==='leave'?'approve_leave':'approve_transfer',id,decision})});
    toast(decision==='approved'?'✓ تم اعتماد الطلب':'تم رفض الطلب',decision==='approved'?'success':'danger');await loadHrApprovals();
  }catch(error){toast(error.message,'danger');}
}

async function loadAttendanceTeam(){
  window.attendanceLoading=true;try{const result=await serverApi('api/attendance.php?team=1');window.liveAttendanceTeam=result.data;if(currentView==='attendance')go('attendance');}
  catch(error){console.warn(error);}finally{window.attendanceLoading=false;}
}

function openGeofenceModal(){
  openFormModal('إضافة موقع تحضير',[
    fmField('المشروع','project_code','select',projects.map(p=>`<option value="${p.id}">${p.name}</option>`).join('')),
    fmField('اسم الموقع','name'),fmField('خط العرض Latitude','latitude','number'),fmField('خط الطول Longitude','longitude','number'),fmField('نصف القطر بالمتر','radius_meters','number',200)
  ].join(''),'حفظ الموقع',async v=>{
    try{await serverApi('api/attendance.php',{method:'POST',body:JSON.stringify({action:'add_geofence',...v})});toast('✓ تمت إضافة موقع التحضير');await loadAttendanceTeam();}
    catch(error){toast(error.message,'danger');}
  });
  for(const key of ['latitude','longitude'])document.querySelector(`[data-field="${key}"]`).setAttribute('step','any');
}

async function deactivateGeofence(id){
  if(!confirm('هل تريد تعطيل موقع التحضير؟ لن يُقبل التسجيل منه بعد ذلك.'))return;
  try{const result=await serverApi('api/attendance.php',{method:'POST',body:JSON.stringify({action:'deactivate_geofence',id})});toast('✓ '+result.message);window.liveAttendanceTeam=null;await loadAttendanceTeam();}
  catch(error){toast(error.message,'danger');}
}

async function markYesterdayAbsences(){
  const d=new Date();d.setDate(d.getDate()-1);const work_date=[d.getFullYear(),String(d.getMonth()+1).padStart(2,'0'),String(d.getDate()).padStart(2,'0')].join('-');
  try{const result=await serverApi('api/attendance.php',{method:'POST',body:JSON.stringify({action:'mark_absences',work_date})});toast(`✓ ${result.message}: ${result.count} موظف`);window.liveAttendanceTeam=null;await loadAttendanceTeam();}
  catch(error){toast(error.message,'danger');}
}

async function loadEmployeeAppData(){
  window.employeeAppLoading=true;try{
    const canPreview=['general-manager','executive-director','projects-director','project-manager','hr-manager'].includes(SERVER_USER.role);
    const query=canPreview?'?emp_code='+encodeURIComponent(empSession.id):'';
    const result=await serverApi('api/attendance.php'+query);window.employeeAttendanceData=result.data;
    const e=result.data.employee;empSession.id=e.emp_code;empSession.name=e.name;empSession.role=e.job_title||SERVER_USER.role;empSession.project=e.project_name||'—';empSession.userAccount=SERVER_USER.email;
    try{const hr=await serverApi('api/hr.php'+query);employeeDetails[e.emp_code]=hr.data;}catch(ignore){}
    if(currentView==='employee-app')document.getElementById('main').innerHTML=renderEmployeeApp();
  }catch(error){if(currentView==='employee-app')toast(error.message,'danger');}finally{window.employeeAppLoading=false;}
}

async function captureFacePresence(){
  if(window.SawaedFaceVerifier&&typeof window.SawaedFaceVerifier.verify==='function')return !!(await window.SawaedFaceVerifier.verify({employeeId:empSession.id}));
  if(!navigator.mediaDevices?.getUserMedia)throw new Error('الكاميرا غير متاحة على هذا الجهاز');
  const stream=await navigator.mediaDevices.getUserMedia({video:{facingMode:'user'},audio:false});
  stream.getTracks().forEach(track=>track.stop());
  return true;
}

function currentPosition(){return new Promise((resolve,reject)=>{if(!navigator.geolocation)return reject(new Error('خدمة الموقع غير متاحة'));navigator.geolocation.getCurrentPosition(p=>resolve({latitude:p.coords.latitude,longitude:p.coords.longitude}),()=>reject(new Error('تعذر قراءة الموقع. فعّل إذن الموقع للتطبيق.')),{enableHighAccuracy:true,timeout:15000,maximumAge:0});});}

async function performAttendance(action){
  try{
    setServerStatus('التحقق من الوجه والموقع','saving');const face=await captureFacePresence();if(!face)throw new Error('فشل التحقق من الوجه');const position=await currentPosition();
    const payload={action,...position,face_verified:true};
    const result=await serverApi('api/attendance.php',{method:'POST',body:JSON.stringify(payload)});toast('✓ '+result.message);setServerStatus('محفوظ');await loadEmployeeAppData();empNav('home');
  }catch(error){toast(error.message,'danger');setServerStatus('تعذر التسجيل','error');}
}

async function loadPayrollRuns(){
  window.payrollLoading=true;try{const list=await serverApi('api/payroll.php');if(list.data.length){const detail=await serverApi('api/payroll.php?id='+list.data[0].id);window.currentPayrollRun=detail.data;}window.payrollLoaded=true;if(currentView==='payroll')document.getElementById('main').innerHTML=renderPayroll();}
  catch(error){console.warn(error);}finally{window.payrollLoading=false;}
}

async function calculatePayroll(){
  const period=document.getElementById('payrollPeriod')?.value;if(!period)return toast('اختر شهر المسير','danger');
  try{setServerStatus('احتساب الرواتب','saving');const result=await serverApi('api/payroll.php',{method:'POST',body:JSON.stringify({action:'calculate',period_key:period})});window.currentPayrollRun=result.data;toast('✓ تم احتساب مسير الرواتب');setServerStatus('محفوظ');go('payroll');}
  catch(error){toast(error.message,'danger');setServerStatus('تعذر الاحتساب','error');}
}

async function payrollRunAction(action){
  const run=window.currentPayrollRun;if(!run)return;
  try{const result=await serverApi('api/payroll.php',{method:'POST',body:JSON.stringify({action,run_id:run.id})});if(action==='post'&&result.journal_entry_id){journalEntries.push({id:'JE-PAY-'+run.period_key+'-'+result.journal_entry_id,date:run.ends_on,desc:'قيد رواتب '+run.period_key,project:'—',debit:Number(run.gross_total),credit:Number(run.gross_total),status:'posted'});scheduleServerSave();}toast('✓ '+result.message);const detail=await serverApi('api/payroll.php?id='+run.id);window.currentPayrollRun=detail.data;go('payroll');}
  catch(error){toast(error.message,'danger');}
}

async function loadFinanceTaxData(refresh=false){if(window.financeTaxLoading)return;window.financeTaxLoading=true;try{const r=await serverApi('api/finance-tax.php');window.financeTaxData=r.data;if(refresh||['finance','tax','reports'].includes(currentView))go(currentView);}catch(e){console.warn('Finance/tax data not loaded',e);if(refresh)toast(e.message,'danger');}finally{window.financeTaxLoading=false;}}
async function financeTaxAction(action,data={}){return serverApi('api/finance-tax.php',{method:'POST',body:JSON.stringify({action,...data})});}
async function postJournal(id){if(!confirm('ترحيل القيد وإقفاله ضد التعديل؟'))return;try{const r=await financeTaxAction('post_journal',{id});toast('✓ '+r.message);await loadFinanceTaxData(true);}catch(e){toast(e.message,'danger');}}
function openReverseJournal(id){openFormModal('قيد عكسي',[fmField('تاريخ العكس','entry_date','date'),fmField('سبب العكس والتصحيح','reason','textarea')].join(''),'إنشاء وترحيل العكس',async v=>{try{const r=await financeTaxAction('reverse_journal',{id,...v});toast('✓ '+r.message);await loadFinanceTaxData(true);}catch(e){toast(e.message,'danger');}});}
function openFiscalPeriodModal(){openFormModal('فترة مالية جديدة',[fmField('رمز الفترة','period_code'),fmField('من','starts_on','date'),fmField('إلى','ends_on','date')].join(''),'إنشاء الفترة',async v=>{try{const r=await financeTaxAction('create_fiscal_period',v);toast('✓ '+r.message);await loadFinanceTaxData(true);}catch(e){toast(e.message,'danger');}});}
async function closeFiscalPeriod(id){if(!confirm('إقفال الفترة ومنع أي ترحيل أو عكس داخلها؟'))return;try{const r=await financeTaxAction('close_fiscal_period',{id});toast('✓ '+r.message);await loadFinanceTaxData(true);}catch(e){toast(e.message,'danger');}}
function openVatReturnModal(){openFormModal('احتساب إقرار VAT',[fmField('رمز الفترة','period_code'),fmField('من','starts_on','date'),fmField('إلى','ends_on','date'),fmField('تاريخ الاستحقاق','due_on','date'),fmField('ملاحظات','notes','textarea')].join(''),'احتساب من الفواتير',async v=>{try{const r=await financeTaxAction('prepare_vat_return',v);toast('✓ '+r.message);await loadFinanceTaxData(true);}catch(e){toast(e.message,'danger');}});}
function openTaxAdjustment(vat_return_id){openFormModal('تعديل على إقرار VAT',[fmField('نوع التعديل','adjustment_type','select','<option value="debit_note">إشعار مدين</option><option value="credit_note">إشعار دائن</option><option value="prior_period">فترة سابقة</option><option value="other">أخرى</option>'),fmField('الوصف والمستند المؤيد','description','textarea'),fmField('القيمة (+/-)','amount','number')].join(''),'إضافة وإعادة الاحتساب',async v=>{try{const r=await financeTaxAction('add_tax_adjustment',{vat_return_id,...v});toast('✓ '+r.message);await loadFinanceTaxData(true);}catch(e){toast(e.message,'danger');}});}
async function approveVatReturn(id){if(!confirm('اعتماد الإقرار بعد مراجعته؟ يجب أن يكون المعتمد مختلفًا عن المُعد.'))return;try{const r=await financeTaxAction('approve_vat_return',{id});toast('✓ '+r.message);await loadFinanceTaxData(true);}catch(e){toast(e.message,'danger');}}
function openVatFiling(id){openFormModal('توثيق تقديم إقرار VAT',[fmField('مرجع التقديم لدى الهيئة','filing_reference')].join(''),'تأكيد التقديم',async v=>{try{const r=await financeTaxAction('file_vat_return',{id,...v});toast('✓ '+r.message);await loadFinanceTaxData(true);}catch(e){toast(e.message,'danger');}});}
function openWhtModal(){const suppliers=(window.supplyData?.suppliers||[]);openFormModal('التزام ضريبة استقطاع',[fmField('المورد','supplier_code','select','<option value="">دون مورد محدد</option>'+suppliers.map(s=>`<option value="${s.code}">${s.name}</option>`).join('')),fmField('تاريخ الدفع','payment_date','date'),fmField('نوع الخدمة','service_type'),fmField('المبلغ الخاضع','taxable_amount','number'),fmField('نسبة الاستقطاع %','rate_percent','number'),fmField('المرجع','reference_number')].join(''),'احتساب الالتزام',async v=>{try{const r=await financeTaxAction('create_wht',v);toast('✓ '+r.message);await loadFinanceTaxData(true);}catch(e){toast(e.message,'danger');}});}
async function approveWht(id){try{const r=await financeTaxAction('approve_wht',{id});toast('✓ '+r.message);await loadFinanceTaxData(true);}catch(e){toast(e.message,'danger');}}
async function payWht(id){try{const r=await financeTaxAction('pay_wht',{id});toast('✓ '+r.message);await loadFinanceTaxData(true);}catch(e){toast(e.message,'danger');}}
async function validateZatcaInvoice(invoice_id){try{const r=await financeTaxAction('validate_zatca_invoice',{invoice_id});toast('✓ '+r.message);await loadFinanceTaxData(true);}catch(e){toast(e.message,'danger');}}

async function loadSupplyData(refresh=false){
  if(window.supplyLoading)return;window.supplyLoading=true;
  try{const [result,procurement]=await Promise.all([serverApi('api/supply.php'),serverApi('api/procurement.php')]);window.supplyData=result.data;window.procurementData=procurement.data;if(refresh||['procurement','suppliers','warehouse','equipment'].includes(currentView))go(currentView);}
  catch(error){console.warn('Supply data not loaded',error);if(refresh)toast(error.message,'danger');}finally{window.supplyLoading=false;}
}

async function supplyAction(action,data={}){return serverApi('api/supply.php',{method:'POST',body:JSON.stringify({action,...data})});}

async function approvePurchaseRequest(id){try{const r=await supplyAction('approve_request',{id});toast('✓ '+r.message);await loadSupplyData(true);}catch(e){toast(e.message,'danger');}}
async function approveSupplier(id){try{const r=await supplyAction('approve_supplier',{id});toast('✓ '+r.message);await loadSupplyData(true);}catch(e){toast(e.message,'danger');}}

function createPurchaseOrder(request_id){
  const suppliers=(window.supplyData?.suppliers||[]).filter(s=>s.status==='active');if(!suppliers.length)return toast('يجب تأهيل مورد أولًا','danger');
  openFormModal('شراء مباشر',[fmField('المورد','supplier_code','select',suppliers.map(s=>`<option value="${s.code}">${s.name}</option>`).join('')),fmField('مبرر الشراء المباشر','direct_purchase_reason','textarea')].join(''),'إصدار الأمر',async v=>{try{const r=await supplyAction('create_order',{request_id,...v});toast('✓ '+r.message);await loadSupplyData(true);}catch(e){toast(e.message,'danger');}});
}

async function procurementAction(action,data={}){return serverApi('api/procurement.php',{method:'POST',body:JSON.stringify({action,...data})});}
function openRfqModal(request_id){openFormModal('طلب عروض أسعار',[fmField('تاريخ الإغلاق','closing_on','date'),fmField('المتطلبات الفنية','technical_requirements','textarea')].join(''),'إنشاء طلب العروض',async v=>{try{const r=await procurementAction('create_rfq',{request_id,...v});toast('✓ '+r.message);await loadSupplyData(true);}catch(e){toast(e.message,'danger');}});}
function openQuotationModal(rfq_id){const suppliers=(window.supplyData?.suppliers||[]).filter(s=>s.status==='active');if(!suppliers.length)return toast('يجب تأهيل مورد أولًا','danger');openFormModal('تسجيل عرض مورد',[fmField('المورد','supplier_code','select',suppliers.map(s=>`<option value="${s.code}">${s.name}</option>`).join('')),fmField('رقم عرض المورد','quotation_code'),fmField('تاريخ العرض','quoted_on','date'),fmField('القيمة قبل الضريبة','total_amount','number'),fmField('التقييم الفني %','technical_score','number'),fmField('مدة التوريد بالأيام','delivery_days','number'),fmField('شروط السداد','payment_terms'),fmField('ملاحظات','notes','textarea')].join(''),'إضافة للمقارنة',async v=>{try{const r=await procurementAction('add_quotation',{rfq_id,...v});toast('✓ '+r.message);await loadSupplyData(true);}catch(e){toast(e.message,'danger');}});}
async function awardQuotation(quotation_id){if(!confirm('ترسية هذا العرض وإصدار أمر الشراء؟'))return;try{const r=await procurementAction('award_quotation',{quotation_id});toast('✓ '+r.message);await loadSupplyData(true);}catch(e){toast(e.message,'danger');}}
function openSupplierInvoiceModal(order_id){openFormModal('فاتورة مورد',[fmField('رقم فاتورة المورد','supplier_invoice_number'),fmField('تاريخ الفاتورة','invoice_date','date'),fmField('تاريخ الاستحقاق','due_date','date'),fmField('المبلغ قبل الضريبة','subtotal','number'),fmField('ضريبة القيمة المضافة','vat_amount','number')].join(''),'تشغيل المطابقة الثلاثية',async v=>{try{const r=await procurementAction('submit_invoice',{order_id,...v});toast('✓ '+r.message);await loadSupplyData(true);}catch(e){toast(e.message,'danger');}});}
async function approveSupplierInvoice(id){if(!confirm('اعتماد الفاتورة وإنشاء قيد الاستحقاق المُرحّل؟'))return;try{const r=await procurementAction('approve_invoice',{id});toast('✓ '+r.message);await loadSupplyData(true);}catch(e){toast(e.message,'danger');}}
function openSupplierPaymentModal(id){const i=window.procurementData?.invoices?.find(x=>Number(x.id)===Number(id));openFormModal('سداد فاتورة مورد',[fmField('المبلغ — المتبقي '+fmt(Number(i?.outstanding||0)),'amount','number'),fmField('تاريخ السداد','paid_on','date'),fmField('طريقة السداد','payment_method','select','<option value="bank_transfer">تحويل بنكي</option><option value="cheque">شيك</option><option value="cash">نقدي</option>'),fmField('رقم المرجع','reference_number')].join(''),'تسجيل السداد',async v=>{try{const r=await procurementAction('pay_invoice',{id,...v});toast('✓ '+r.message);await loadSupplyData(true);}catch(e){toast(e.message,'danger');}});}
function openWarehouseScopeModal(){const d=window.supplyData||{},p=window.procurementData||{};if(!(d.warehouses||[]).length||!(p.users||[]).length)return toast('أضف مستودعًا ومستخدمًا تشغيليًا أولًا','danger');openFormModal('صلاحية مستخدم على مستودع',[fmField('المستودع','warehouse_id','select',d.warehouses.map(w=>`<option value="${w.id}">${w.name}</option>`).join('')),fmField('المستخدم','user_id','select',p.users.map(u=>`<option value="${u.id}">${u.name} — ${u.role}</option>`).join('')),fmField('الصلاحية','role_scope','select','<option value="storekeeper">أمين مستودع</option><option value="manager">مدير مستودع</option><option value="viewer">عرض فقط</option>')].join(''),'حفظ الصلاحية',async v=>{try{const r=await procurementAction('assign_warehouse_scope',v);toast('✓ '+r.message);await loadSupplyData(true);}catch(e){toast(e.message,'danger');}});}

function receivePurchaseOrder(order_id){
  const warehouses=window.supplyData?.warehouses||[];if(!warehouses.length)return toast('أنشئ مستودعًا أولًا','danger');
  openFormModal('تسجيل استلام أمر الشراء',[fmField('المستودع','warehouse_id','select',warehouses.map(w=>`<option value="${w.id}">${w.name}</option>`).join('')),fmField('ملاحظات الاستلام','notes','textarea')].join(''),'تسجيل الاستلام',async v=>{try{const r=await supplyAction('receive_order',{order_id,...v});toast('✓ '+r.message);await loadSupplyData(true);}catch(e){toast(e.message,'danger');}});
}

async function inspectReceipt(receipt_id,decision){if(!confirm(decision==='accepted'?'اعتماد الفحص وإضافة الكميات المقبولة إلى الرصيد؟':'رفض الاستلام بالكامل؟'))return;try{const r=await supplyAction('inspect_receipt',{receipt_id,decision});toast('✓ '+r.message);await loadSupplyData(true);}catch(e){toast(e.message,'danger');}}

function openWarehouseModal(){openFormModal('مستودع جديد',[fmField('الرمز','code'),fmField('الاسم','name'),fmField('المشروع','project_code','select','<option value="">مستودع مركزي</option>'+projects.map(p=>`<option value="${p.id}">${p.name}</option>`).join(''))].join(''),'حفظ',async v=>{try{const r=await supplyAction('create_warehouse',v);toast('✓ '+r.message);await loadSupplyData(true);}catch(e){toast(e.message,'danger');}});}

function openInventoryItemModal(){openFormModal('صنف مخزون جديد',[fmField('رمز الصنف','code'),fmField('اسم الصنف','name'),fmField('الوحدة','unit'),fmField('حد إعادة الطلب','reorder_level','number')].join(''),'حفظ',async v=>{try{const r=await supplyAction('create_item',v);toast('✓ '+r.message);await loadSupplyData(true);}catch(e){toast(e.message,'danger');}});}

function openStockIssueModal(){
  const d=window.supplyData||{};if(!(d.warehouses||[]).length||!(d.items||[]).length)return toast('يجب إنشاء مستودع وصنف أولًا','danger');
  openFormModal('صرف مادة لمشروع',[fmField('المستودع','warehouse_id','select',d.warehouses.map(w=>`<option value="${w.id}">${w.name}</option>`).join('')),fmField('الصنف','item_code','select',d.items.map(i=>`<option value="${i.code}">${i.name} (${i.code})</option>`).join('')),fmField('المشروع المستفيد','project_code','select',projects.map(p=>`<option value="${p.id}">${p.name}</option>`).join('')),fmField('الكمية','quantity','number')].join(''),'صرف',async v=>{try{const r=await supplyAction('issue_stock',v);toast('✓ '+r.message);await loadSupplyData(true);}catch(e){toast(e.message,'danger');}});
}

function openEquipmentTransferModal(equipment_code){openFormModal('محضر نقل معدة',[fmField('المعدة','equipment_code','select',(window.supplyData?.equipment||[]).map(e=>`<option value="${e.code}" ${e.code===equipment_code?'selected':''}>${e.name} (${e.code})</option>`).join('')),fmField('المشروع المستلم','to_project_code','select',projects.map(p=>`<option value="${p.id}">${p.name}</option>`).join('')),fmField('تاريخ النقل','transferred_on','date'),fmField('ملاحظات','notes','textarea')].join(''),'إرسال للاعتماد',async v=>{try{const r=await supplyAction('request_equipment_transfer',v);toast('✓ '+r.message);await loadSupplyData(true);}catch(e){toast(e.message,'danger');}});}

async function approveEquipmentTransfer(id){try{const r=await supplyAction('approve_equipment_transfer',{id});toast('✓ '+r.message);await loadSupplyData(true);}catch(e){toast(e.message,'danger');}}

async function loadOperationsData(refresh=false){if(window.operationsLoading)return;window.operationsLoading=true;try{const r=await serverApi('api/operations.php');window.operationsData=r.data;if(refresh||['maintenance','quality','safety','documents'].includes(currentView))go(currentView);}catch(e){console.warn('Operations data not loaded',e);if(refresh)toast(e.message,'danger');}finally{window.operationsLoading=false;}}
async function operationsAction(action,data={}){return serverApi('api/operations.php',{method:'POST',body:JSON.stringify({action,...data})});}
function openMaintenanceDiagnosis(id){openFormModal('تشخيص أمر الصيانة',[fmField('التشخيص','diagnosis','textarea'),fmField('التكلفة التقديرية','estimated_cost','number')].join(''),'إرسال للاعتماد',async v=>{try{const r=await operationsAction('diagnose_work_order',{id,...v});toast('✓ '+r.message);await loadOperationsData(true);}catch(e){toast(e.message,'danger');}});}
async function approveMaintenance(id){try{const r=await operationsAction('approve_work_order',{id});toast('✓ '+r.message);await loadOperationsData(true);}catch(e){toast(e.message,'danger');}}
async function startMaintenance(id){try{const r=await operationsAction('start_work_order',{id});toast('✓ '+r.message);await loadOperationsData(true);}catch(e){toast(e.message,'danger');}}
function openMaintenanceCompletion(id){openFormModal('إنهاء تنفيذ الصيانة',[fmField('تكلفة العمل الفعلية','actual_cost','number'),fmField('تكلفة قطع الغيار','parts_cost','number'),fmField('تفاصيل العمل المنفذ','notes','textarea')].join(''),'إرسال للفحص',async v=>{try{const r=await operationsAction('complete_work_order',{id,...v});toast('✓ '+r.message);await loadOperationsData(true);}catch(e){toast(e.message,'danger');}});}
async function closeMaintenance(id){if(!confirm('تم اختبار المعدة وتعمل بصورة سليمة؟'))return;try{const r=await operationsAction('close_work_order',{id});toast('✓ '+r.message);await Promise.all([loadOperationsData(true),loadSupplyData()]);}catch(e){toast(e.message,'danger');}}
function openInspectionResult(id){openFormModal('نتيجة فحص الجودة',[fmField('النتيجة','result','select','<option value="passed">مطابق / مقبول</option><option value="failed">غير مطابق / مرفوض</option>'),fmField('الشدة عند الرفض','severity','select','<option value="low">منخفضة</option><option value="medium">متوسطة</option><option value="high">عالية</option><option value="critical">حرجة</option>'),fmField('تاريخ إغلاق الإجراء المستهدف','target_date','date'),fmField('نتائج وملاحظات الفحص','findings','textarea')].join(''),'حفظ النتيجة',async v=>{try{const r=await operationsAction('complete_quality_inspection',{id,...v});toast('✓ '+r.message);await loadOperationsData(true);}catch(e){toast(e.message,'danger');}});}
function openCorrectiveAction(id){openFormModal('الإجراء التصحيحي',[fmField('تحليل السبب الجذري','root_cause','textarea'),fmField('الإجراء التصحيحي المنفذ','corrective_action','textarea')].join(''),'إرسال للتحقق',async v=>{try{const r=await operationsAction('submit_corrective_action',{id,...v});toast('✓ '+r.message);await loadOperationsData(true);}catch(e){toast(e.message,'danger');}});}
async function closeNcr(id){if(!confirm('تم التحقق المستقل من فعالية الإجراء التصحيحي؟'))return;try{const r=await operationsAction('close_ncr',{id});toast('✓ '+r.message);await loadOperationsData(true);}catch(e){toast(e.message,'danger');}}
async function approveSafetyPermit(id){try{const r=await operationsAction('approve_safety_permit',{id});toast('✓ '+r.message);await loadOperationsData(true);}catch(e){toast(e.message,'danger');}}
async function closeSafetyPermit(id){try{const r=await operationsAction('close_safety_permit',{id});toast('✓ '+r.message);await loadOperationsData(true);}catch(e){toast(e.message,'danger');}}
function openStopWorkModal(){openFormModal('إيقاف عمل فوري',[fmField('المشروع','project_code','select',projects.map(p=>`<option value="${p.id}">${p.name}</option>`).join('')),fmField('وصف الخطر الداهم','hazard_description','textarea')].join(''),'إيقاف العمل',async v=>{try{const r=await operationsAction('stop_work',v);toast('✓ '+r.message);await loadOperationsData(true);}catch(e){toast(e.message,'danger');}});}
function openResumeWorkModal(id){openFormModal('توثيق تصحيح الخطر',[fmField('الإجراء التصحيحي ودليل إزالة الخطر','corrective_action','textarea')].join(''),'استئناف العمل',async v=>{try{const r=await operationsAction('resume_work',{id,...v});toast('✓ '+r.message);await loadOperationsData(true);}catch(e){toast(e.message,'danger');}});}
function openIncidentModal(){openFormModal('بلاغ حادث / شبه حادث',[fmField('المشروع','project_code','select',projects.map(p=>`<option value="${p.id}">${p.name}</option>`).join('')),fmField('النوع','incident_type','select','<option value="near_miss">شبه حادث</option><option value="first_aid">إسعاف أولي</option><option value="lost_time">إصابة وقت ضائع</option><option value="property_damage">تلف ممتلكات</option>'),fmField('الشدة','severity','select','<option value="low">منخفضة</option><option value="medium">متوسطة</option><option value="high">عالية</option><option value="critical">حرجة</option>'),fmField('وقت الحادث','occurred_at','datetime-local'),fmField('أيام العمل المفقودة','lost_time_days','number'),fmField('الوصف','description','textarea'),fmField('الإجراء الفوري','immediate_action','textarea')].join(''),'تسجيل البلاغ',async v=>{try{const r=await operationsAction('report_incident',v);toast('✓ '+r.message);await loadOperationsData(true);}catch(e){toast(e.message,'danger');}});}
async function closeIncident(id){if(!confirm('تمت مراجعة التحقيق والإجراء الفوري؟'))return;try{const r=await operationsAction('close_incident',{id});toast('✓ '+r.message);await loadOperationsData(true);}catch(e){toast(e.message,'danger');}}
function openToolboxTalkModal(){openFormModal('اجتماع السلامة اليومي',[fmField('المشروع','project_code','select',projects.map(p=>`<option value="${p.id}">${p.name}</option>`).join('')),fmField('التاريخ','talk_date','date'),fmField('الموضوع','topic'),fmField('عدد الحضور','attendee_count','number')].join(''),'حفظ السجل',async v=>{try{const r=await operationsAction('record_toolbox_talk',v);toast('✓ '+r.message);await loadOperationsData(true);}catch(e){toast(e.message,'danger');}});}
async function approveDocument(id){try{const r=await operationsAction('approve_document',{id});toast('✓ '+r.message);await loadOperationsData(true);}catch(e){toast(e.message,'danger');}}
function openCorrespondenceModal(){const docs=window.operationsData?.documents||[];openFormModal('مراسلة مشروع',[fmField('الاتجاه','direction','select','<option value="inbound">وارد</option><option value="outbound">صادر</option>'),fmField('المشروع','project_code','select','<option value="">عام</option>'+projects.map(p=>`<option value="${p.id}">${p.name}</option>`).join('')),fmField('الموضوع','subject'),fmField('الطرف الآخر','counterparty'),fmField('رقم المرجع الخارجي','reference_number'),fmField('تاريخ المراسلة','correspondence_date','date'),fmField('تاريخ الرد/الاستحقاق','due_on','date'),fmField('المستند المرفق','document_id','select','<option value="">دون مرفق</option>'+docs.map(d=>`<option value="${d.id}">${d.document_code||d.original_name} v${d.version_no}</option>`).join(''))].join(''),'حفظ المراسلة',async v=>{try{const r=await operationsAction('create_correspondence',v);toast('✓ '+r.message);await loadOperationsData(true);}catch(e){toast(e.message,'danger');}});}
async function closeCorrespondence(id){try{const r=await operationsAction('close_correspondence',{id});toast('✓ '+r.message);await loadOperationsData(true);}catch(e){toast(e.message,'danger');}}

async function loadContractsData(refresh=false){if(window.contractsLoading)return;window.contractsLoading=true;try{const r=await serverApi('api/contracts.php');window.contractsData=r.data;if(refresh||['contracts','customers','reports'].includes(currentView))go(currentView);}catch(e){console.warn('Contracts data not loaded',e);if(refresh)toast(e.message,'danger');}finally{window.contractsLoading=false;}}
async function contractAction(action,data={}){return serverApi('api/contracts.php',{method:'POST',body:JSON.stringify({action,...data})});}

function openCustomerContractModal(){openFormModal('عقد عميل جديد',[fmField('رمز العقد','contract_code'),fmField('رمز العميل','customer_code'),fmField('اسم العميل','customer_name'),fmField('الرقم الضريبي','vat_number'),fmField('المشروع','project_code','select',projects.map(p=>`<option value="${p.id}">${p.name}</option>`).join('')),fmField('قيمة العقد','original_value','number'),fmField('نسبة المحتجز %','retention_rate','number',10),fmField('الدفعة المقدمة','advance_amount','number',0),fmField('أجل السداد بالأيام','payment_terms_days','number',30),fmField('تاريخ البداية','starts_on','date'),fmField('تاريخ النهاية','ends_on','date')].join(''),'حفظ العقد',async v=>{try{const r=await contractAction('create_customer_contract',v);toast('✓ '+r.message);await loadContractsData(true);}catch(e){toast(e.message,'danger');}});}

function openCustomerClaimModal(){const contracts=window.contractsData?.customerContracts||[];if(!contracts.length)return toast('أنشئ عقد عميل أولًا','danger');openFormModal('مستخلص عميل جديد',[fmField('العقد','contract_id','select',contracts.map(c=>`<option value="${c.id}">${c.contract_code} — ${c.project_name}</option>`).join('')),fmField('نهاية الفترة','period_end','date'),fmField('قيمة أعمال الفترة','gross_work_value','number'),fmField('استرداد الدفعة المقدمة','advance_recovery','number',0),fmField('خصومات أخرى','other_deductions','number',0)].join(''),'إرسال للاعتماد',async v=>{try{const r=await contractAction('create_customer_claim',v);toast('✓ '+r.message);await loadContractsData(true);}catch(e){toast(e.message,'danger');}});}
async function approveCustomerClaim(id){try{const r=await contractAction('approve_customer_claim',{id});toast('✓ '+r.message);await loadContractsData(true);}catch(e){toast(e.message,'danger');}}
async function issueCustomerInvoice(claim_id){if(!confirm('إصدار الفاتورة وإنشاء قيد محاسبي مُرحّل؟'))return;try{const r=await contractAction('issue_invoice',{claim_id});toast('✓ '+r.message);await loadContractsData(true);}catch(e){toast(e.message,'danger');}}

function openCollectionModal(invoice_id){const invoice=window.contractsData?.invoices?.find(i=>Number(i.id)===Number(invoice_id));openFormModal('تسجيل تحصيل فاتورة',[fmField('المبلغ — المتبقي '+fmt(Number(invoice?.outstanding||0)),'amount','number'),fmField('تاريخ التحصيل','collected_on','date'),fmField('طريقة السداد','payment_method','select','<option value="bank_transfer">تحويل بنكي</option><option value="cheque">شيك</option><option value="cash">نقدي</option>'),fmField('رقم المرجع','reference_number')].join(''),'تسجيل التحصيل',async v=>{try{const r=await contractAction('record_collection',{invoice_id,...v});toast('✓ '+r.message);await loadContractsData(true);}catch(e){toast(e.message,'danger');}});}

function openSubcontractModal(){openFormModal('عقد مقاول باطن جديد',[fmField('رمز العقد','contract_code'),fmField('رمز المقاول','subcontractor_code'),fmField('اسم المقاول','subcontractor_name'),fmField('الرقم الضريبي','vat_number'),fmField('المشروع','project_code','select',projects.map(p=>`<option value="${p.id}">${p.name}</option>`).join('')),fmField('نطاق العمل','scope_of_work','textarea'),fmField('قيمة العقد','contract_value','number'),fmField('نسبة المحتجز %','retention_rate','number',10),fmField('الدفعة المقدمة','advance_amount','number',0)].join(''),'حفظ العقد',async v=>{try{const r=await contractAction('create_subcontract',v);toast('✓ '+r.message);await loadContractsData(true);}catch(e){toast(e.message,'danger');}});}
function openSubcontractClaimModal(){const contracts=window.contractsData?.subcontracts||[];if(!contracts.length)return toast('أنشئ عقد مقاول باطن أولًا','danger');openFormModal('مستخلص مقاول باطن',[fmField('العقد','contract_id','select',contracts.map(c=>`<option value="${c.id}">${c.contract_code} — ${c.subcontractor_name}</option>`).join('')),fmField('نهاية الفترة','period_end','date'),fmField('قيمة أعمال الفترة','gross_work_value','number'),fmField('استرداد دفعة مقدمة','advance_recovery','number',0),fmField('خصومات أخرى','other_deductions','number',0)].join(''),'إرسال للاعتماد',async v=>{try{const r=await contractAction('create_subcontract_claim',v);toast('✓ '+r.message);await loadContractsData(true);}catch(e){toast(e.message,'danger');}});}
async function approveSubcontractClaim(id){if(!confirm('اعتماد المستخلص وإنشاء قيد الاستحقاق المُرحّل؟'))return;try{const r=await contractAction('approve_subcontract_claim',{id});toast('✓ '+r.message);await loadContractsData(true);}catch(e){toast(e.message,'danger');}}

async function loadProjectControls(projectCode=null,refresh=false){if(window.controlsLoading)return;window.controlsLoading=true;try{const url='api/project-controls.php'+(projectCode?'?project_code='+encodeURIComponent(projectCode):'');const r=await serverApi(url);window.projectControlsData=r.data;window.projectControlsScope=projectCode||'*';if(projectWorkspaceId&&projectCode===projectWorkspaceId)renderProjectWorkspace();else if(refresh)go(currentView);}catch(e){console.warn('Project controls not loaded',e);if(refresh)toast(e.message,'danger');}finally{window.controlsLoading=false;}}
async function controlsAction(action,data={}){return serverApi('api/project-controls.php',{method:'POST',body:JSON.stringify({action,...data})});}
async function refreshControls(){await loadProjectControls(projectWorkspaceId||null,true);}

function openBudgetLineModal(project_code=projectWorkspaceId){openFormModal('بند موازنة / WBS',[fmField('رمز WBS','wbs_code'),fmField('الوصف','description'),fmField('تصنيف التكلفة','cost_category','select','<option value="materials">مواد</option><option value="labor">عمالة</option><option value="subcontract">مقاول باطن</option><option value="equipment">معدات</option><option value="overhead">مصروفات</option>'),fmField('التكلفة المخططة','planned_cost','number'),fmField('وزن الإنجاز %','weight_percent','number')].join(''),'إضافة',async v=>{try{const r=await controlsAction('add_budget_line',{project_code,...v});toast('✓ '+r.message);await refreshControls();}catch(e){toast(e.message,'danger');}});}
async function approveProjectBudget(id){try{const r=await controlsAction('approve_budget',{id});toast('✓ '+r.message);await refreshControls();}catch(e){toast(e.message,'danger');}}
function openProgressUpdateModal(project_code=projectWorkspaceId){openFormModal('تحديث تقدم المشروع',[fmField('تاريخ التقرير','as_of_date','date'),fmField('التقدم المخطط %','planned_progress','number'),fmField('التقدم الفعلي %','actual_progress','number'),fmField('التكلفة التراكمية','cumulative_cost','number'),fmField('ملخص الأعمال والمعوقات','narrative','textarea')].join(''),'إرسال للاعتماد',async v=>{try{const r=await controlsAction('submit_progress',{project_code,...v});toast('✓ '+r.message);await refreshControls();}catch(e){toast(e.message,'danger');}});}
async function approveProgressUpdate(id){try{const r=await controlsAction('approve_progress',{id});toast('✓ '+r.message);await refreshControls();}catch(e){toast(e.message,'danger');}}
function openChangeOrderModal(project_code=projectWorkspaceId){openFormModal('أمر تغيير جديد',[fmField('المشروع','project_code','select',projects.map(p=>`<option value="${p.id}" ${p.id===project_code?'selected':''}>${p.name}</option>`).join('')),fmField('العنوان','title'),fmField('الوصف','description','textarea'),fmField('التغير في القيمة (+/-)','value_change','number'),fmField('الأثر الزمني بالأيام (+/-)','time_impact_days','number'),fmField('السبب والأساس','reason','textarea')].join(''),'إرسال للاعتماد',async v=>{try{const r=await controlsAction('create_change_order',v);toast('✓ '+r.message);await loadProjectControls(currentView==='projects'?projectWorkspaceId:null,true);}catch(e){toast(e.message,'danger');}});}
async function approveChangeOrder(id){if(!confirm('اعتماد أمر التغيير وتحديث قيمة ومدة المشروع؟'))return;try{const r=await controlsAction('approve_change_order',{id});toast('✓ '+r.message);await loadProjectControls(currentView==='projects'?projectWorkspaceId:null,true);}catch(e){toast(e.message,'danger');}}
function openContractClaimModal(project_code=projectWorkspaceId){openFormModal('مطالبة تعاقدية / تأخير',[fmField('المشروع','project_code','select',projects.map(p=>`<option value="${p.id}" ${p.id===project_code?'selected':''}>${p.name}</option>`).join('')),fmField('نوع المطالبة','claim_type','select','<option value="delay">تمديد مدة</option><option value="cost">تكلفة إضافية</option><option value="disruption">تعطيل إنتاجية</option><option value="scope">تغيير نطاق</option>'),fmField('تاريخ الحدث','event_date','date'),fmField('تاريخ الإشعار','notice_date','date'),fmField('الوصف والأساس التعاقدي','description','textarea'),fmField('المبلغ المطالب به','claimed_amount','number'),fmField('الأيام المطالب بها','claimed_days','number')].join(''),'تسجيل المطالبة',async v=>{try{const r=await controlsAction('create_contract_claim',v);toast('✓ '+r.message);await loadProjectControls(currentView==='projects'?projectWorkspaceId:null,true);}catch(e){toast(e.message,'danger');}});}
async function approveContractClaim(id){try{const r=await controlsAction('approve_contract_claim',{id});toast('✓ '+r.message);await loadProjectControls(currentView==='projects'?projectWorkspaceId:null,true);}catch(e){toast(e.message,'danger');}}
function openCashflowModal(project_code=projectWorkspaceId){openFormModal('توقع تدفق نقدي',[fmField('الشهر','forecast_month','month'),fmField('المقبوضات المتوقعة','expected_inflow','number'),fmField('المدفوعات المتوقعة','expected_outflow','number'),fmField('ملاحظات','notes','textarea')].join(''),'حفظ التوقع',async v=>{try{const r=await controlsAction('save_cashflow',{project_code,...v});toast('✓ '+r.message);await refreshControls();}catch(e){toast(e.message,'danger');}});}
function openProjectRiskModal(project_code=projectWorkspaceId){openFormModal('خطر مشروع جديد',[fmField('الخطر','title'),fmField('الاحتمالية','probability','select','<option value="low">منخفضة</option><option value="medium">متوسطة</option><option value="high">عالية</option>'),fmField('الأثر','impact','select','<option value="low">منخفض</option><option value="medium">متوسط</option><option value="high">عالٍ</option>'),fmField('إجراء التخفيف','mitigation','textarea'),fmField('مالك الخطر','owner_name')].join(''),'حفظ',async v=>{try{const r=await controlsAction('create_risk',{project_code,...v});toast('✓ '+r.message);await refreshControls();}catch(e){toast(e.message,'danger');}});}

async function uploadDocumentToServer() {
  const file = document.getElementById('documentFile')?.files?.[0];
  if (!file) return toast('اختر ملفًا أولًا', 'danger');
  const body = new FormData();
  body.append('file', file);
  body.append('category', document.getElementById('documentCategory').value);
  body.append('project_code', document.getElementById('documentProject').value);
  body.append('title', document.getElementById('documentTitle').value);
  body.append('document_code', document.getElementById('documentCode').value);
  body.append('expires_on', document.getElementById('documentExpiry').value);
  try {
    setServerStatus('جارٍ الرفع', 'saving');
    const response = await fetch('api/upload.php', {method:'POST', headers:{'X-CSRF-Token':SERVER_CSRF}, body});
    const result = await response.json();
    if (!response.ok || !result.ok) throw new Error(result.message || 'تعذر الرفع');
    closeFormModal();
    toast('✓ تم رفع المستند وحفظه على الخادم');
    setServerStatus('محفوظ');
    await loadOperationsData(true);
  } catch (error) {
    toast(error.message, 'danger');
    setServerStatus('تعذر الحفظ', 'error');
  }
}

document.addEventListener('visibilitychange', () => {
  if (document.visibilityState === 'hidden' && serverSaveTimer) persistCoreState();
});
hydrateFromServer();
