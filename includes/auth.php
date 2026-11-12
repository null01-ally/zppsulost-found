<?php
function db(): PDO {
 static $pdo;
 return $pdo ??= new PDO('mysql:host='.(getenv('DB_HOST') ?: 'localhost').';dbname='.(getenv('DB_NAME') ?: 'lost_found_db').';charset=utf8mb4',getenv('DB_USER') ?: 'root',getenv('DB_PASS') ?: '',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false,PDO::MYSQL_ATTR_INIT_COMMAND=>"SET time_zone='+08:00'"]);
}
function app_url(string $path=''): string {
 $root=str_replace('\\','/',dirname(__DIR__));$doc=rtrim(str_replace('\\','/',$_SERVER['DOCUMENT_ROOT'] ?? ''),'/');
 $base=$doc && str_starts_with(strtolower($root),strtolower($doc).'/') ? substr($root,strlen($doc)) : '';
 return $base.'/'.ltrim($path,'/');
}
function go(string $path): void { header('Location: '.app_url($path));exit; }
function current_user(): ?array {
 static $loaded=false,$user=null;
 if($loaded)return $user;$loaded=true;
 if(empty($_SESSION['account_id']))return null;
 if(time()-(int)($_SESSION['last_active'] ?? 0)>1800 || time()-(int)($_SESSION['signed_in'] ?? 0)>28800){unset($_SESSION['account_id']);return null;}
 $s=db()->prepare('SELECT a.*,u.name FROM accounts a JOIN users u ON u.id=a.user_id WHERE a.id=?');$s->execute([$_SESSION['account_id']]);$record=$s->fetch();
 if(!$record || !$record['active'] || (int)$record['session_version']!==(int)($_SESSION['session_version'] ?? 0)){unset($_SESSION['account_id']);return null;}
 $_SESSION['last_active']=time();return $user=$record;
}
function require_login(): array { header('Cache-Control: private, no-store');$user=current_user();if(!$user)go('login.php');return $user; }
function is_admin(): bool { return (current_user()['role'] ?? '')==='admin'; }
function require_admin(): array { $user=require_login();if($user['role']!=='admin'){http_response_code(403);exit('Administrator access required.');}return $user; }
function sign_in(array $user): void {session_regenerate_id(true);$_SESSION=['account_id'=>(int)$user['id'],'session_version'=>(int)$user['session_version'],'signed_in'=>time(),'last_active'=>time(),'csrf'=>bin2hex(random_bytes(32))];}
function password_input(string $key='password'): string {return is_string($_POST[$key] ?? null) ? $_POST[$key] : '';}
function check_password(string $password): void {if(mb_strlen($password)<10 || strlen($password)>72)throw new RuntimeException('Use a password of at least 10 characters (up to 72 bytes).');}
