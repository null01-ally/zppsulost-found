<?php
require __DIR__.'/includes/app.php';require_admin();$s=db()->prepare('SELECT * FROM recovery_attachments WHERE id=?');$s->execute([(int)field($_GET,'id')]);$file=$s->fetch();
if(!$file || basename($file['stored_name'])!==$file['stored_name'] || !is_file(__DIR__.'/database/verification/'.$file['stored_name'])){http_response_code(404);exit('File not found.');}
header('Content-Type: application/octet-stream');header('X-Content-Type-Options: nosniff');header('Cache-Control: private, no-store');header('Content-Security-Policy: sandbox');header('Content-Disposition: attachment; filename="verification-'.(int)$file['id'].'.'.pathinfo($file['stored_name'],PATHINFO_EXTENSION).'"');readfile(__DIR__.'/database/verification/'.$file['stored_name']);
