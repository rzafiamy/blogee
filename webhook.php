<?php
// public_html/webhook.php
// PHP 8.3 FPM optimized webhook receiver

// 1. Configuration & .env Loader
if (file_exists(__DIR__ . '/.env')) {
    $env = parse_ini_file(__DIR__ . '/.env');
    foreach ($env as $key => $value) {
        putenv("$key=$value");
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
    die('Unauthorized API Request. Verify your GITHUB_WEBHOOK_SECRET.');
}

$decoded_payload = json_decode($payload, true);
$is_manual = isset($decoded_payload['action']) && $decoded_payload['action'] === 'manual_trigger';

// 3. Execute Sync (Git Pull or Archive Download)
$sync_method = getenv('SYNC_METHOD') ?: 'git';
$safe_dir    = escapeshellarg($repo_dir);

if (!is_dir($repo_dir)) {
    if (!mkdir($repo_dir, 0755, true)) {
        $error_msg = "Error: Content directory [{$repo_dir}] not found and could not be created. Check permissions.";
        log_webhook($error_msg);
        http_response_code(500);
        die($error_msg);
    }
}

$output = "";
if ($sync_method === 'archive') {
    $repo_url = getenv('REPO_ARCHIVE_URL');
    if (!$repo_url || str_contains($repo_url, 'your-username')) {
        $error_msg = "Error: REPO_ARCHIVE_URL is not configured.";
        log_webhook($error_msg);
        http_response_code(400);
        die($error_msg);
    }
    
    $output = php_sync_archive($repo_url, $repo_dir);
} else {
    // Standard Git Pull (remains as fallback if enabled)
    if (!function_exists('shell_exec')) {
        $output = "Error: shell_exec is disabled. Use SYNC_METHOD=archive for pure PHP sync.";
    } elseif (!is_dir($repo_dir . '/.git')) {
        $output = "Error: {$repo_dir} is not a git repository.";
    } else {
        $output = shell_exec("cd {$safe_dir} && git checkout main && git pull origin main 2>&1");
    }
}

/**
 * Pure PHP Archive Sync (No shell_exec required)
 */
function php_sync_archive($url, $target) {
    $temp_file = sys_get_temp_dir() . '/blogee_' . md5($url) . '.tmp';
    
    // 1. Download via file_get_contents (with context for GitHub)
    $context = stream_context_create([
        "http" => [
            "method" => "GET",
            "header" => "User-Agent: Blogee-CMS-Sync\r\n" .
                        "Accept: application/vnd.github+json\r\n",
            "follow_location" => 1,
            "timeout" => 60
        ]
    ]);

    $data = @file_get_contents($url, false, $context);
    if ($data === false) {
        return "Error: Failed to download archive from $url. Check allow_url_fopen.";
    }
    file_put_contents($temp_file, $data);

    // 2. Determine type and extract
    $result = "Files synchronized:\n";
    try {
        if (str_contains($url, 'zipball') || str_ends_with($url, '.zip')) {
            if (!class_exists('ZipArchive')) throw new Exception("ZipArchive extension missing.");
            $zip = new ZipArchive;
            if ($zip->open($temp_file) === TRUE) {
                // First entry is usually the root folder
                $root = $zip->getNameIndex(0);
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $entry = $zip->getNameIndex($i);
                    $relativePath = substr($entry, strlen($root));
                    if (!$relativePath) continue;
                    
                    $fullPath = $target . '/' . $relativePath;
                    if (str_ends_with($entry, '/')) {
                        if (!is_dir($fullPath)) mkdir($fullPath, 0755, true);
                    } else {
                        $dir = dirname($fullPath);
                        if (!is_dir($dir)) mkdir($dir, 0755, true);
                        copy("zip://".$temp_file."#".$entry, $fullPath);
                        $result .= "- $relativePath\n";
                    }
                }
                $zip->close();
            } else {
                throw new Exception("Failed to open ZIP archive.");
            }
        } else {
            // Assume Tarball (tar.gz)
            if (!class_exists('PharData')) throw new Exception("PharData extension missing for tarballs. Use zipball URL.");
            $phar = new PharData($temp_file);
            $extracted = $phar->extractTo($target, null, true);
            // PharData doesn't easily allow stripping on extract, so we look for the folder
            $it = new DirectoryIterator($target);
            foreach ($it as $file) {
                if ($file->isDir() && !$file->isDot() && str_contains($file->getFilename(), '-')) {
                    // This is likely the GitHub folder (e.g. user-repo-hash)
                    $subDir = $file->getPathname();
                    move_folder_contents($subDir, $target);
                    rmdir($subDir);
                    break;
                }
            }
        }
    } catch (Exception $e) {
        @unlink($temp_file);
        return "Error: " . $e->getMessage();
    }

    @unlink($temp_file);
    return $result;
}

function move_folder_contents($src, $dst) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
    foreach ($it as $item) {
        $target = $dst . DIRECTORY_SEPARATOR . $it->getSubPathName();
        if ($item->isDir()) {
            if (!is_dir($target)) mkdir($target, 0755, true);
        } else {
            if (!is_dir(dirname($target))) mkdir(dirname($target), 0755, true);
            rename($item->getPathname(), $target);
        }
    }
}

function log_webhook($msg) {
    $log_file = __DIR__ . '/webhook-pull.log';
    $log_entry = date('Y-m-d H:i:s') . ' - ' . stripslashes($msg) . PHP_EOL;
    @file_put_contents($log_file, $log_entry, FILE_APPEND);
}

// 4. Log the result
$log_msg = $output ?: 'No output from sync command';
log_webhook($log_msg);

// Return status
if (str_contains(strtolower($log_msg), 'error') || str_contains(strtolower($log_msg), 'fatal')) {
    http_response_code(500);
    echo "Sync failed: " . $log_msg;
} else {
    http_response_code(200);
    echo "Sync successful.\n" . ($is_manual ? "Output: " . $log_msg : "");
}
?>

