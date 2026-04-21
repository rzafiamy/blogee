<?php
// public_html/webhook.php
// PHP 8.3 FPM optimized webhook receiver

// 1. Configuration & .env Loader
if (file_exists(__DIR__ . '/.env')) {
    $env = parse_ini_file(__DIR__ . '/.env');
    if (isset($env['GITHUB_WEBHOOK_SECRET'])) {
        putenv("GITHUB_WEBHOOK_SECRET={$env['GITHUB_WEBHOOK_SECRET']}");
    }
}

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

// 3. Execute Sync (Git Pull or Archive Download)
$sync_method = getenv('SYNC_METHOD') ?: 'git';
$safe_dir    = escapeshellarg($repo_dir);

if (!is_dir($repo_dir)) {
    // Attempt to create the directory if it doesn't exist
    if (!mkdir($repo_dir, 0755, true)) {
        $error_msg = "Error: Content directory not found and could not be created at {$repo_dir}.";
        log_webhook($error_msg);
        http_response_code(500);
        die($error_msg);
    }
}

if ($sync_method === 'archive') {
    $repo_url = getenv('REPO_ARCHIVE_URL');
    if (!$repo_url) {
        $error_msg = "Error: REPO_ARCHIVE_URL not set in .env";
        log_webhook($error_msg);
        http_response_code(500);
        die($error_msg);
    }
    // Using curl + tar to download and extract, stripping the top-level folder GitHub/GitLab adds
    $output = shell_exec("curl -sL " . escapeshellarg($repo_url) . " | tar -xz --strip-components=1 -C " . $safe_dir . " 2>&1");
} else {
    // Standard Git Pull
    $output = shell_exec("cd {$safe_dir} && git checkout main && git pull origin main 2>&1");
}

function log_webhook($msg) {
    $log_file = __DIR__ . '/webhook-pull.log';
    $log_entry = date('Y-m-d H:i:s') . ' - ' . $msg . PHP_EOL;
    if (is_writable(__DIR__) || (file_exists($log_file) && is_writable($log_file))) {
        file_put_contents($log_file, $log_entry, FILE_APPEND);
    }
}

// 4. Log the result
log_webhook($output ?: 'No output from sync command');


http_response_code(200);
echo 'Pull executed successfully.';
?>
