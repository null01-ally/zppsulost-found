<?php
// Shared validation, escaping and request helpers. No schema changes run on page loads.
require_once dirname(__DIR__).'/db.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params(['httponly'=>true, 'samesite'=>'Lax', 'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off']);
    session_start();
}
date_default_timezone_set('Asia/Manila');
const CATEGORIES = ['Electronics', 'IDs', 'Books', 'Keys', 'Personal Items', 'Other'];
function e($value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function field(array $source, string $key): string { return is_string($source[$key] ?? null) ? trim($source[$key]) : ''; }
function token(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="'.token().'">'; }
function verify_post(): void {
    if ($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); header('Allow: POST'); exit('POST required.'); }
    $limit=ini_get('post_max_size');
    $bytes=(int)$limit * (['g'=>1073741824,'m'=>1048576,'k'=>1024][strtolower(substr($limit,-1))] ?? 1);
    if($bytes>0 && (int)($_SERVER['CONTENT_LENGTH'] ?? 0)>$bytes) { http_response_code(413); exit('The form exceeds the server upload limit. Use smaller files and submit again.'); }
    if (!is_string($_POST['csrf'] ?? null) || !hash_equals(token(),$_POST['csrf'])) { http_response_code(403); exit('Form expired. Reload this page and try again.'); }
}
function valid_date(string $date): bool { $d=DateTimeImmutable::createFromFormat('!Y-m-d',$date); return $d && $d->format('Y-m-d')===$date && $date<=date('Y-m-d'); }
function image_url(?string $name, string $prefix=''): string {
    // Demo photos may use HTTPS product image sources; uploads still use local filenames.
    if ($name && filter_var($name,FILTER_VALIDATE_URL) && parse_url($name,PHP_URL_SCHEME)==='https') return $name;
    return $name && basename($name)===$name && is_file(dirname(__DIR__).'/uploads/'.$name)
        ? $prefix.'uploads/'.rawurlencode($name) : $prefix.'assets/item-placeholder.svg';
}
function upload_image(): ?string {
    $f=$_FILES['image'] ?? null;
    if (!$f || ($f['error'] ?? null)===UPLOAD_ERR_NO_FILE) return null;
    if (!is_int($f['error'] ?? null) || $f['error']!==UPLOAD_ERR_OK || $f['size']>5*1024*1024) throw new RuntimeException('Choose an image smaller than 5 MB. Check your PHP upload limit too.');
    $types=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']); $size=@getimagesize($f['tmp_name']);
    if (!isset($types[$mime]) || !$size || $size[0]>8000 || $size[1]>8000) throw new RuntimeException('Use a valid JPG, PNG or WebP image up to 8000 pixels per side.');
    $dir=dirname(__DIR__).'/uploads';
    if (!is_dir($dir) && !mkdir($dir,0755,true)) throw new RuntimeException('Image storage is unavailable.');
    $name=bin2hex(random_bytes(20)).'.'.$types[$mime];
    if (!move_uploaded_file($f['tmp_name'],$dir.'/'.$name)) throw new RuntimeException('The image could not be saved.');
    return $name;
}
// IP-based limits persist across browser sessions; no client IP headers are trusted.
function throttle(string $action, int $limit=10): void {
    global $conn;
    $bucket=hash('sha256',$action.':'.($_SERVER['REMOTE_ADDR'] ?? 'local'));
    $s=$conn->prepare('INSERT IGNORE INTO request_limits (bucket,window_start,hits) VALUES (?,NOW(),0)'); $s->bind_param('s',$bucket); $s->execute();
    $s=$conn->prepare('UPDATE request_limits SET hits=IF(window_start<DATE_SUB(NOW(),INTERVAL 1 HOUR),1,hits+1), window_start=IF(window_start<DATE_SUB(NOW(),INTERVAL 1 HOUR),NOW(),window_start) WHERE bucket=?'); $s->bind_param('s',$bucket); $s->execute();
    $s=$conn->prepare('SELECT hits FROM request_limits WHERE bucket=?'); $s->bind_param('s',$bucket); $s->execute();
    if ((int)$s->get_result()->fetch_assoc()['hits']>$limit) throw new RuntimeException('Too many submissions. Please try again in an hour.');
}

/** Validate supporting evidence before saving it outside the public uploads folder.
 * Apache blocks database/; evidence is available only to operators on the server.
 */
function upload_verification_files(): array {
    $files=$_FILES['verification_files'] ?? null;
    if (!$files) return [];
    if (!is_array($files['error'] ?? null) || count($files['error'])>3) throw new RuntimeException('Attach up to three supporting files.');
    $allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','application/pdf'=>'pdf'];
    $validated=[]; $saved=[];
    foreach($files['error'] as $i=>$error) {
        if($error===UPLOAD_ERR_NO_FILE) continue;
        if($error!==UPLOAD_ERR_OK || !isset($files['size'][$i]) || $files['size'][$i]>5*1024*1024) throw new RuntimeException('Each supporting file must be JPG, PNG, WebP or PDF, up to 5 MB. Check your PHP upload limits too.');
        $tmp=$files['tmp_name'][$i] ?? '';
        if(!is_string($tmp) || !is_uploaded_file($tmp)) throw new RuntimeException('The supporting file could not be accepted.');
        $mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp);
        if(!isset($allowed[$mime])) throw new RuntimeException('Only JPG, PNG, WebP images and PDF documents are accepted.');
        if(str_starts_with($mime,'image/')) {
            $size=@getimagesize($tmp);
            if(!$size || $size[0]>8000 || $size[1]>8000) throw new RuntimeException('Supporting images must be valid and no larger than 8000 pixels per side.');
        } elseif(file_get_contents($tmp,false,null,0,5)!=='%PDF-') {
            throw new RuntimeException('Choose a valid PDF document.');
        }
        $original=is_string($files['name'][$i] ?? null) ? $files['name'][$i] : 'supporting-file';
        $original=mb_substr(preg_replace('/[\x00-\x1F\x7F]/u','',basename(str_replace('\\','/',$original))),0,150);
        $validated[]=['tmp'=>$tmp,'file'=>bin2hex(random_bytes(20)).'.'.$allowed[$mime],'name'=>$original,'mime'=>$mime];
    }
    $dir=dirname(__DIR__).'/database/verification';
    if($validated && !is_dir($dir) && !mkdir($dir,0700,true)) throw new RuntimeException('Private verification storage is unavailable.');
    try {
        foreach($validated as $file) {
            if(!move_uploaded_file($file['tmp'],$dir.'/'.$file['file'])) throw new RuntimeException('Unable to save supporting evidence. Please try again.');
            $saved[]=$file;
        }
    } catch(Throwable $error) {
        foreach($saved as $file) @unlink($dir.'/'.$file['file']);
        throw $error;
    }
    return $saved;
}


require_once __DIR__.'/auth.php';
