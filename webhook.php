<?php
// public_html/webhook.php
// PHP 8.3 FPM optimized webhook receiver

// 1. Configuration - In production, use environment variables!
$secret_key = getenv('GITHUB_WEBHOOK_SECRET') ?: 'PLEASE_CHANGE_ME_TO_A_SECURE_TOKEN';
$repo_dir   = __DIR__ . '/content';    // Path to cloned repository

// 2. Validate GitHub HMAC-SHA256 Signature
$signature          = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
$payload            = file_get_contents('php://input');
$expected_signature = 'sha256=' . hash_hmac('sha256', $payload, $secret_key);

// Constant-time comparison prevents timing attacks
if (!hash_equals($expected_signature, $signature)) {
    http_response_code(403);
    die('Unauthorized API Request.');
}

// 3. Execute HTTPS Git Pull (no credentials needed for public repo)
$safe_dir = escapeshellarg($repo_dir);

if (!is_dir($repo_dir)) {
    $error_msg = "Error: Content directory not found at {$repo_dir}. Please clone your content repo first.";
    file_put_contents(__DIR__ . '/webhook-pull.log', date('Y-m-d H:i:s') . ' - ' . $error_msg . PHP_EOL, FILE_APPEND);
    http_response_code(500);
    die($error_msg);
}

// Ensure we are on the main branch and pull latest
$output   = shell_exec("cd {$safe_dir} && git checkout main && git pull origin main 2>&1");


// 4. Append result to log file
file_put_contents(
    __DIR__ . '/webhook-pull.log',
    date('Y-m-d H:i:s') . ' - ' . $output . PHP_EOL,
    FILE_APPEND
);

http_response_code(200);
echo 'Pull executed successfully.';
?>
