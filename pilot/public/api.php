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
if ($method==='GET'&&$path==='/api/patients') {
 $q=trim((string)($_GET['q']??'')); if (mb_strlen($q)>100) json_response(['error'=>'Search query is too long.'],422);
 $sql='SELECT id,first_name,last_name,date_of_birth,sex,phone,barangay,created_at FROM patients'; $params=[];
 if ($q!=='') { $sql.=" WHERE first_name LIKE :q ESCAPE '\\' OR last_name LIKE :q ESCAPE '\\' OR id=:id"; $params[':q']='%'.addcslashes($q,'\\%_').'%'; $params[':id']=$q; }
 $sql.=' ORDER BY last_name,first_name LIMIT 200'; $s=db()->prepare($sql); $s->execute($params); json_response(['patients'=>$s->fetchAll()]);
}
if ($method==='POST'&&$path==='/api/patients') {
 require_role($u,['admin','provider','encoder']); require_csrf(); $d=request_json();
 $first=required_string($d,'firstName',100,'First name'); $last=required_string($d,'lastName',100,'Last name'); $dob=required_string($d,'dateOfBirth',10,'Date of birth');
 if (!valid_date($dob)||$dob>(new DateTimeImmutable('today'))->format('Y-m-d')) json_response(['error'=>'Enter a valid date of birth that is not in the future.'],422);
 $sex=required_string($d,'sex',20,'Sex'); if (!in_array($sex,['female','male','intersex','unknown'],true)) json_response(['error'=>'Select a valid sex value.'],422);
 $barangay=required_string($d,'barangay',120,'Barangay'); $phone=optional_string($d,'phone',40,'Phone'); $id=new_uuid(); $pdo=db();
 try { $s=$pdo->prepare('INSERT INTO patients(id,first_name,last_name,date_of_birth,sex,phone,barangay,created_by) VALUES (:id,:f,:l,:dob,:sex,:phone,:barangay,:actor)'); $s->execute([':id'=>$id,':f'=>$first,':l'=>$last,':dob'=>$dob,':sex'=>$sex,':phone'=>$phone,':barangay'=>$barangay,':actor'=>$u['id']]); audit($pdo,(int)$u['id'],'create','patient',$id); }
 catch (Throwable $e) { error_log('CHITS patient create failed: '.$e->getMessage()); json_response(['error'=>'Patient could not be saved. No record was confirmed.'],500); }
 $s=$pdo->prepare('SELECT id,first_name,last_name,date_of_birth,sex,phone,barangay,created_at FROM patients WHERE id=:id'); $s->execute([':id'=>$id]); json_response(['patient'=>$s->fetch()],201);
}
if (preg_match('#^/api/patients/([a-f0-9-]{36})$#i',$path,$m)&&$method==='GET') {
 $s=db()->prepare('SELECT id,first_name,last_name,date_of_birth,sex,phone,barangay,created_at FROM patients WHERE id=:id'); $s->execute([':id'=>$m[1]]); $p=$s->fetch(); if (!$p) json_response(['error'=>'Patient not found.'],404);
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
