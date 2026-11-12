<?php
// Environment overrides keep credentials out of application templates.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
set_exception_handler(function(Throwable $error): void {
    error_log((string)$error); http_response_code(500);
    echo '<h1>Service temporarily unavailable</h1><p>Please try again shortly.</p>';
});
$conn=new mysqli(getenv('DB_HOST') ?: 'localhost',getenv('DB_USER') ?: 'root',getenv('DB_PASS') ?: '',getenv('DB_NAME') ?: 'lost_found_db');
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '+08:00'");
