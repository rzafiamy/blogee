<?php
// public_html/api.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$content_dir = __DIR__ . '/content/posts';
$posts = [];

if (is_dir($content_dir)) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($content_dir));
    foreach ($iterator as $file) {
        if ($file->isDir() || $file->getExtension() !== 'md') continue;
        
        $filePath = $file->getPathname();
        $content = file_get_contents($filePath);
        $title = 'Untitled'; 
        $date = 'Unknown Date'; 
        $tags = [];
        $description = '';
        $markdown_body = $content;

        // Extract category from folder name (relative to content/posts)
        $relativeDir = str_replace($content_dir, '', dirname($filePath));
        $rawCategory = trim($relativeDir, DIRECTORY_SEPARATOR);
        $category = $rawCategory ? str_replace(DIRECTORY_SEPARATOR, ' / ', $rawCategory) : 'Uncategorized';

        $slug = basename($filePath, '.md');
        // Extract YAML frontmatter block
        if (preg_match('/^---\s*(.*?)\s*---\s*(.*)/s', $content, $matches)) {
            $frontmatter   = $matches[1];
            $markdown_body = $matches[2];
            
            if (preg_match('/title:\s*"(.*?)"/', $frontmatter, $m)) {
                $title = $m[1];
            } elseif (preg_match('/title:\s*(.*)/', $frontmatter, $m)) {
                $title = trim($m[1], " '\"");
            }

            if (preg_match('/slug:\s*"(.*?)"/', $frontmatter, $m)) {
                $slug = $m[1];
            } elseif (preg_match('/slug:\s*(.*)/', $frontmatter, $m)) {
                $slug = trim($m[1], " '\"");
            }

            if (preg_match('/date:\s*"(.*?)"/', $frontmatter, $m)) {
                $date = $m[1];
            } elseif (preg_match('/date:\s*(.*)/', $frontmatter, $m)) {
                $date = trim($m[1], " '\"");
            }

            if (preg_match('/description:\s*"(.*?)"/s', $frontmatter, $m)) {
                $description = $m[1];
            } elseif (preg_match('/description:\s*(.*)/', $frontmatter, $m)) {
                $description = trim($m[1], " '\"");
            }

            if (preg_match('/tags:\s*\[(.*?)\]/', $frontmatter, $m)) {
                $tags_string = str_replace(['"', "'"], '', $m[1]);
                $tags = array_map('trim', explode(',', $tags_string));
            }
        }

        // Helper to get hierarchical path
        $dateParts = explode('-', $date);
        $year  = $dateParts[0] ?? '0000';
        $month = $dateParts[1] ?? '00';
        $day   = $dateParts[2] ?? '00';
        $cleanCategory = strtolower(str_replace(' / ', '/', $category));
        $fullPath = "{$cleanCategory}/{$year}/{$month}/{$day}/{$slug}";

        $posts[] = [
            'id'          => md5(basename($filePath)),
            'slug'        => $slug,
            'path'        => $fullPath,
            'title'       => $title,
            'description' => $description,
            'date'        => $date,
            'category'    => $category,
            'tags'        => array_values(array_filter($tags)),
            'rawMarkdown' => trim($markdown_body),
        ];
    }
}


// Sort by date descending (newest first)
usort($posts, function($a, $b) {
    $t1 = strtotime($a['date']);
    $t2 = strtotime($b['date']);
    return $t2 - $t1;
});

echo json_encode($posts);
?>
