<?php
// security.php
// Security headers and settings
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.gc_maxlifetime', 3600);

// Set secure headers
header("Content-Security-Policy: " . 
	"default-src 'self'; " .
	"style-src 'self' 'unsafe-inline'; " .
	"img-src 'self' data: ; " .
	"frame-ancestors 'none'; " .
	"form-action 'self';"
);
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: same-origin");
header("Permissions-Policy: geolocation=(), camera=(), microphone=()");

// Set secure cookie parameters
ini_set('session.cookie_domain', '');
ini_set('session.cookie_path', '/');
ini_set('session.cookie_lifetime', 0);

// Start session if needed
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}
?>