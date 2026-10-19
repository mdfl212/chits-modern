<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/src/bootstrap.php';
$secure=!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off';
session_name('chits_pilot');
session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>$secure,'httponly'=>true,'samesite'=>'Strict']);
session_start();
$path=preg_replace('#/+#','/',parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH)?:'/');
$method=strtoupper($_SERVER['REQUEST_METHOD']??'GET');
if ($method==='GET'&&$path==='/api/csrf') { if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32)); json_response(['csrfToken'=>$_SESSION['csrf_token'],'authenticated'=>current_user()!==null]); }
if ($method==='POST'&&$path==='/api/login') {
 require_csrf(); $d=request_json(); $username=required_string($d,'username',80,'Username'); $password=(string)($d['password']??'');
 if ($password===''||strlen($password)>1024) json_response(['error'=>'Invalid username or password.'],401);
 $s=db()->prepare('SELECT id,username,password_hash,role FROM users WHERE username=:u AND active=1'); $s->execute([':u'=>$username]); $row=$s->fetch();
 if (!$row||!password_verify($password,$row['password_hash'])) { usleep(350000); json_response(['error'=>'Invalid username or password.'],401); }
 session_regenerate_id(true); $_SESSION['user_id']=(int)$row['id']; $_SESSION['csrf_token']=bin2hex(random_bytes(32)); audit(db(),(int)$row['id'],'login','user',(string)$row['id']);
 json_response(['user'=>['username'=>$row['username'],'role'=>$row['role']],'csrfToken'=>$_SESSION['csrf_token']]);
}
if ($method==='POST'&&$path==='/api/logout') { $u=require_user(); require_csrf(); audit(db(),(int)$u['id'],'logout','user',(string)$u['id']); $_SESSION=[]; if (ini_get('session.use_cookies')) { $p=session_get_cookie_params(); setcookie(session_name(),'', ['expires'=>time()-42000,'path'=>$p['path'],'secure'=>$p['secure'],'httponly'=>true,'samesite'=>'Strict']); } session_destroy(); json_response(['ok'=>true]); }
$u=require_user();
if ($method==='GET'&&$path==='/api/me') json_response(['user'=>['username'=>$u['username'],'role'=>$u['role']]]);
if ($method==='GET'&&$path==='/api/households') {
 $q=trim((string)($_GET['q']??'')); if (mb_strlen($q)>100) json_response(['error'=>'Search query is too long.'],422);
 $sql="SELECT h.id,h.household_code,h.household_head,h.barangay,h.address,h.created_at,COUNT(p.id) AS member_count FROM households h LEFT JOIN patients p ON p.household_id=h.id";
 $params=[]; if ($q!=='') { $sql.=" WHERE h.household_code LIKE :q ESCAPE '\\' OR h.household_head LIKE :q ESCAPE '\\' OR h.barangay LIKE :q ESCAPE '\\'"; $params[':q']='%'.addcslashes($q,'\\%_').'%'; }
 $sql.=' GROUP BY h.id ORDER BY h.household_head LIMIT 200'; $s=db()->prepare($sql); $s->execute($params); json_response(['households'=>$s->fetchAll()]);
}
if ($method==='POST'&&$path==='/api/households') {
 require_role($u,['admin','provider','encoder']); require_csrf(); $d=request_json();
 $head=required_string($d,'householdHead',160,'Household head'); $barangay=required_string($d,'barangay',120,'Barangay'); $code=optional_string($d,'householdCode',40,'Household code')??('HH-'.strtoupper(bin2hex(random_bytes(4)))); $address=optional_string($d,'address',250,'Address')??''; $id=new_uuid(); $pdo=db();
 try { $s=$pdo->prepare('INSERT INTO households(id,household_code,household_head,barangay,address,created_by) VALUES (:id,:code,:head,:barangay,:address,:actor)'); $s->execute([':id'=>$id,':code'=>$code,':head'=>$head,':barangay'=>$barangay,':address'=>$address,':actor'=>$u['id']]); audit($pdo,(int)$u['id'],'create','household',$id); }
 catch (PDOException $e) { if (str_contains(strtolower($e->getMessage()),'unique')) json_response(['error'=>'That household code is already in use. Choose a different code.'],409); error_log('CHITS household create failed: '.$e->getMessage()); json_response(['error'=>'Household could not be saved. No record was confirmed.'],500); }
 json_response(['household'=>['id'=>$id,'household_code'=>$code,'household_head'=>$head,'barangay'=>$barangay,'address'=>$address,'member_count'=>0]],201);
}
if (preg_match('#^/api/households/([a-f0-9-]{36})$#i',$path,$m)&&$method==='PATCH') {
 require_role($u,['admin','provider','encoder']); require_csrf(); $d=request_json(); $id=$m[1]; $pdo=db();
 $s=$pdo->prepare('SELECT id FROM households WHERE id=:id'); $s->execute([':id'=>$id]); if (!$s->fetch()) json_response(['error'=>'Household not found.'],404);
 $head=required_string($d,'householdHead',160,'Household head'); $barangay=required_string($d,'barangay',120,'Barangay'); $code=required_string($d,'householdCode',40,'Household code'); $address=optional_string($d,'address',250,'Address')??'';
 $s=$pdo->prepare('SELECT COUNT(*) FROM patients WHERE household_id=:id AND lower(barangay)<>lower(:barangay)'); $s->execute([':id'=>$id,':barangay'=>$barangay]); if ((int)$s->fetchColumn()>0) json_response(['error'=>'Cannot change this household’s barangay while linked patients have a different barangay. Reassign or update those patients first.'],422);
 try { $s=$pdo->prepare('UPDATE households SET household_code=:code,household_head=:head,barangay=:barangay,address=:address WHERE id=:id'); $s->execute([':code'=>$code,':head'=>$head,':barangay'=>$barangay,':address'=>$address,':id'=>$id]); audit($pdo,(int)$u['id'],'update','household',$id); }
 catch (PDOException $e) { if (str_contains(strtolower($e->getMessage()),'unique')) json_response(['error'=>'That household code is already in use. Choose a different code.'],409); error_log('CHITS household update failed: '.$e->getMessage()); json_response(['error'=>'Household could not be updated. No success was confirmed.'],500); }
 json_response(['household'=>['id'=>$id,'household_code'=>$code,'household_head'=>$head,'barangay'=>$barangay,'address'=>$address]]);
}
if (preg_match('#^/api/households/([a-f0-9-]{36})$#i',$path,$m)&&$method==='PATCH') {
 require_role($u,['admin','provider','encoder']); require_csrf(); $d=request_json(); $id=$m[1]; $pdo=db();
 $s=$pdo->prepare('SELECT id FROM households WHERE id=:id'); $s->execute([':id'=>$id]); if (!$s->fetch()) json_response(['error'=>'Household not found.'],404);
 $head=required_string($d,'householdHead',160,'Household head'); $barangay=required_string($d,'barangay',120,'Barangay'); $code=required_string($d,'householdCode',40,'Household code'); $address=optional_string($d,'address',250,'Address')??'';
 $s=$pdo->prepare('SELECT COUNT(*) FROM patients WHERE household_id=:id AND lower(barangay)<>lower(:barangay)'); $s->execute([':id'=>$id,':barangay'=>$barangay]); if ((int)$s->fetchColumn()>0) json_response(['error'=>'Cannot change household barangay while linked patients have a different barangay. Reassign or update those patients first.'],422);
 try { $s=$pdo->prepare('UPDATE households SET household_code=:code,household_head=:head,barangay=:barangay,address=:address WHERE id=:id'); $s->execute([':code'=>$code,':head'=>$head,':barangay'=>$barangay,':address'=>$address,':id'=>$id]); audit($pdo,(int)$u['id'],'update','household',$id); }
 catch (PDOException $e) { if (str_contains(strtolower($e->getMessage()),'unique')) json_response(['error'=>'That household code is already in use. Choose a different code.'],409); error_log('CHITS household update failed: '.$e->getMessage()); json_response(['error'=>'Household could not be updated. No success was confirmed.'],500); }
 json_response(['household'=>['id'=>$id,'household_code'=>$code,'household_head'=>$head,'barangay'=>$barangay,'address'=>$address]]);
}
if (preg_match('#^/api/households/([a-f0-9-]{36})$#i',$path,$m)&&$method==='GET') {
 $s=db()->prepare('SELECT id,household_code,household_head,barangay,address,created_at FROM households WHERE id=:id'); $s->execute([':id'=>$m[1]]); $h=$s->fetch(); if (!$h) json_response(['error'=>'Household not found.'],404);
 $s=db()->prepare('SELECT id,first_name,last_name,date_of_birth,sex,barangay FROM patients WHERE household_id=:id ORDER BY last_name,first_name'); $s->execute([':id'=>$m[1]]); json_response(['household'=>$h,'members'=>$s->fetchAll()]);
}
if ($method==='GET'&&$path==='/api/patients') {
 $q=trim((string)($_GET['q']??'')); if (mb_strlen($q)>100) json_response(['error'=>'Search query is too long.'],422);
 $sql='SELECT id,first_name,last_name,date_of_birth,sex,phone,barangay,household_id,created_at FROM patients'; $params=[];
 if ($q!=='') { $sql.=" WHERE first_name LIKE :q ESCAPE '\\' OR last_name LIKE :q ESCAPE '\\' OR id=:id"; $params[':q']='%'.addcslashes($q,'\\%_').'%'; $params[':id']=$q; }
 $sql.=' ORDER BY last_name,first_name LIMIT 200'; $s=db()->prepare($sql); $s->execute($params); json_response(['patients'=>$s->fetchAll()]);
}
if ($method==='POST'&&$path==='/api/patients') {
 require_role($u,['admin','provider','encoder']); require_csrf(); $d=request_json();
 $first=required_string($d,'firstName',100,'First name'); $last=required_string($d,'lastName',100,'Last name'); $dob=required_string($d,'dateOfBirth',10,'Date of birth');
 if (!valid_date($dob)||$dob>(new DateTimeImmutable('today'))->format('Y-m-d')) json_response(['error'=>'Enter a valid date of birth that is not in the future.'],422);
 $sex=required_string($d,'sex',20,'Sex'); if (!in_array($sex,['female','male','intersex','unknown'],true)) json_response(['error'=>'Select a valid sex value.'],422);
 $barangay=required_string($d,'barangay',120,'Barangay'); $phone=optional_string($d,'phone',40,'Phone'); $householdId=optional_string($d,'householdId',36,'Household'); $id=new_uuid(); $pdo=db();
 if ($householdId!==null) { $hs=$pdo->prepare('SELECT id,barangay FROM households WHERE id=:id'); $hs->execute([':id'=>$householdId]); $household=$hs->fetch(); if (!$household) json_response(['error'=>'Selected household was not found.'],422); if (mb_strtolower($household['barangay'])!==mb_strtolower($barangay)) json_response(['error'=>'Patient barangay must match the selected household barangay.'],422); }
 try { $s=$pdo->prepare('INSERT INTO patients(id,first_name,last_name,date_of_birth,sex,phone,barangay,household_id,created_by) VALUES (:id,:f,:l,:dob,:sex,:phone,:barangay,:household,:actor)'); $s->execute([':id'=>$id,':f'=>$first,':l'=>$last,':dob'=>$dob,':sex'=>$sex,':phone'=>$phone,':barangay'=>$barangay,':household'=>$householdId,':actor'=>$u['id']]); audit($pdo,(int)$u['id'],'create','patient',$id); }
 catch (Throwable $e) { error_log('CHITS patient create failed: '.$e->getMessage()); json_response(['error'=>'Patient could not be saved. No record was confirmed.'],500); }
 $s=$pdo->prepare('SELECT id,first_name,last_name,date_of_birth,sex,phone,barangay,household_id,created_at FROM patients WHERE id=:id'); $s->execute([':id'=>$id]); json_response(['patient'=>$s->fetch()],201);
}
if (preg_match('#^/api/patients/([a-f0-9-]{36})/household$#i',$path,$m)&&$method==='PATCH') {
 require_role($u,['admin','provider','encoder']); require_csrf(); $d=request_json(); $id=$m[1]; $pdo=db();
 $s=$pdo->prepare('SELECT id,barangay FROM patients WHERE id=:id'); $s->execute([':id'=>$id]); $patient=$s->fetch(); if (!$patient) json_response(['error'=>'Patient not found.'],404);
 $householdId=optional_string($d,'householdId',36,'Household');
 if ($householdId!==null) { $s=$pdo->prepare('SELECT id,barangay FROM households WHERE id=:id'); $s->execute([':id'=>$householdId]); $h=$s->fetch(); if (!$h) json_response(['error'=>'Selected household was not found.'],422); if (mb_strtolower($h['barangay'])!==mb_strtolower($patient['barangay'])) json_response(['error'=>'Patient barangay must match the selected household barangay.'],422); }
 try { $s=$pdo->prepare('UPDATE patients SET household_id=:household,updated_at=CURRENT_TIMESTAMP WHERE id=:id'); $s->execute([':household'=>$householdId,':id'=>$id]); audit($pdo,(int)$u['id'],'update','patient_household',$id); }
 catch (Throwable $e) { error_log('CHITS patient household update failed: '.$e->getMessage()); json_response(['error'=>'Patient household link could not be updated. No success was confirmed.'],500); }
 json_response(['patient'=>['id'=>$id,'household_id'=>$householdId]]);
}
if (preg_match('#^/api/patients/([a-f0-9-]{36})/household$#i',$path,$m)&&$method==='PATCH') {
 require_role($u,['admin','provider','encoder']); require_csrf(); $d=request_json(); $id=$m[1]; $pdo=db();
 $s=$pdo->prepare('SELECT id,barangay FROM patients WHERE id=:id'); $s->execute([':id'=>$id]); $patient=$s->fetch(); if (!$patient) json_response(['error'=>'Patient not found.'],404);
 $householdId=optional_string($d,'householdId',36,'Household');
 if ($householdId!==null) { $s=$pdo->prepare('SELECT id,barangay FROM households WHERE id=:id'); $s->execute([':id'=>$householdId]); $h=$s->fetch(); if (!$h) json_response(['error'=>'Selected household was not found.'],422); if (mb_strtolower($h['barangay'])!==mb_strtolower($patient['barangay'])) json_response(['error'=>'Patient barangay must match the selected household barangay.'],422); }
 try { $s=$pdo->prepare('UPDATE patients SET household_id=:household,updated_at=CURRENT_TIMESTAMP WHERE id=:id'); $s->execute([':household'=>$householdId,':id'=>$id]); audit($pdo,(int)$u['id'],'update','patient_household',$id); }
 catch (Throwable $e) { error_log('CHITS patient household update failed: '.$e->getMessage()); json_response(['error'=>'Patient household link could not be updated. No success was confirmed.'],500); }
 json_response(['patient'=>['id'=>$id,'household_id'=>$householdId]]);
}
if (preg_match('#^/api/patients/([a-f0-9-]{36})$#i',$path,$m)&&$method==='GET') {
 $s=db()->prepare('SELECT id,first_name,last_name,date_of_birth,sex,phone,barangay,household_id,created_at FROM patients WHERE id=:id'); $s->execute([':id'=>$m[1]]); $p=$s->fetch(); if (!$p) json_response(['error'=>'Patient not found.'],404);
 $s=db()->prepare('SELECT c.id,c.visit_date,c.chief_complaint,c.subjective,c.objective,c.assessment,c.plan,c.temperature_c,c.bp_systolic,c.bp_diastolic,c.pulse,c.respiratory_rate,c.spo2,c.created_at,u.username AS provider FROM consultations c JOIN users u ON u.id=c.provider_id WHERE c.patient_id=:id ORDER BY c.visit_date DESC,c.created_at DESC LIMIT 200'); $s->execute([':id'=>$m[1]]); json_response(['patient'=>$p,'consultations'=>$s->fetchAll()]);
}
if (preg_match('#^/api/patients/([a-f0-9-]{36})/consultations$#i',$path,$m)&&$method==='POST') {
 require_role($u,['admin','provider']); require_csrf(); $d=request_json(); $patientId=$m[1]; $pdo=db(); $check=$pdo->prepare('SELECT id FROM patients WHERE id=:id'); $check->execute([':id'=>$patientId]); if (!$check->fetch()) json_response(['error'=>'Patient not found.'],404);
 $visit=required_string($d,'visitDate',10,'Visit date'); if (!valid_date($visit)||$visit>(new DateTimeImmutable('today'))->format('Y-m-d')) json_response(['error'=>'Enter a valid visit date that is not in the future.'],422);
 $complaint=required_string($d,'chiefComplaint',500,'Chief complaint'); $fields=[];
 foreach (['subjective'=>10000,'objective'=>10000,'assessment'=>10000,'plan'=>10000] as $k=>$max) $fields[$k]=optional_string($d,$k,$max,ucfirst($k))??'';
 $numeric=[];
 foreach (['temperatureC'=>['temperature_c',25,45,'Temperature'],'bpSystolic'=>['bp_systolic',40,300,'Systolic blood pressure'],'bpDiastolic'=>['bp_diastolic',20,200,'Diastolic blood pressure'],'pulse'=>['pulse',20,250,'Pulse'],'respiratoryRate'=>['respiratory_rate',4,80,'Respiratory rate'],'spo2'=>['spo2',50,100,'SpO2']] as $k=>$spec) { [$column,$min,$max,$label]=$spec; $v=$d[$k]??null; if ($v===''||$v===null) {$numeric[$column]=null;continue;} if (!is_numeric($v)||(float)$v<$min||(float)$v>$max) json_response(['error'=>$label.' is outside the accepted input range.'],422); $numeric[$column]=$v+0; }
 $id=new_uuid(); $s=$pdo->prepare('INSERT INTO consultations(id,patient_id,visit_date,chief_complaint,subjective,objective,assessment,plan,temperature_c,bp_systolic,bp_diastolic,pulse,respiratory_rate,spo2,provider_id) VALUES (:id,:patient,:date,:complaint,:subjective,:objective,:assessment,:plan,:temp,:sys,:dia,:pulse,:rr,:spo2,:provider)');
 try { $pdo->beginTransaction(); $s->execute([':id'=>$id,':patient'=>$patientId,':date'=>$visit,':complaint'=>$complaint,':subjective'=>$fields['subjective'],':objective'=>$fields['objective'],':assessment'=>$fields['assessment'],':plan'=>$fields['plan'],':temp'=>$numeric['temperature_c'],':sys'=>$numeric['bp_systolic'],':dia'=>$numeric['bp_diastolic'],':pulse'=>$numeric['pulse'],':rr'=>$numeric['respiratory_rate'],':spo2'=>$numeric['spo2'],':provider'=>$u['id']]); audit($pdo,(int)$u['id'],'create','consultation',$id); $pdo->commit(); }
 catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); error_log('CHITS consultation create failed: '.$e->getMessage()); json_response(['error'=>'Consultation could not be saved. No record was confirmed.'],500); }
 json_response(['consultation'=>['id'=>$id,'patient_id'=>$patientId,'visit_date'=>$visit,'chief_complaint'=>$complaint]],201);
}
json_response(['error'=>'Endpoint not found.'],404);
