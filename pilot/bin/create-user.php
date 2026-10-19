<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require_once dirname(__DIR__).'/src/bootstrap.php';
$username=trim($argv[1]??''); $role=$argv[2]??'admin';
if ($username===''||strlen($username)>80||!preg_match('/^[A-Za-z0-9._-]+$/',$username)) { fwrite(STDERR,"Usage: php pilot/bin/create-user.php username [admin|provider|encoder|viewer]\n"); exit(2); }
if (!in_array($role,['admin','provider','encoder','viewer'],true)) { fwrite(STDERR,"Invalid role.\n"); exit(2); }
fwrite(STDOUT,"Password (minimum 14 characters): ");
if (stripos(PHP_OS,'WIN')!==0) system('stty -echo');
$password=trim((string)fgets(STDIN));
if (stripos(PHP_OS,'WIN')!==0) { system('stty echo'); fwrite(STDOUT,"\n"); }
if (strlen($password)<14||strlen($password)>1024) { fwrite(STDERR,"Password must be between 14 and 1024 characters.\n"); exit(2); }
try { $s=db()->prepare('INSERT INTO users(username,password_hash,role) VALUES (:u,:p,:r)'); $s->execute([':u'=>$username,':p'=>password_hash($password,PASSWORD_DEFAULT),':r'=>$role]); fwrite(STDOUT,"Account created for ".$username." (".$role.").\n"); }
catch (Throwable $e) { error_log('CHITS account provisioning failed: '.$e->getMessage()); fwrite(STDERR,"Could not create account. Username may already exist or database is unavailable.\n"); exit(1); }
