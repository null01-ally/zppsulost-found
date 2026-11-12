<?php
require __DIR__.'/includes/app.php';verify_post();$_SESSION=[];
$params=session_get_cookie_params();session_destroy();setcookie(session_name(),'', ['expires'=>time()-3600,'path'=>$params['path'],'secure'=>$params['secure'],'httponly'=>true,'samesite'=>'Lax']);go('login.php');
