<?php
// public_html/api.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$content_dir = __DIR__ . '/content/posts';
$files = glob($content_dir . '/*.md');
$posts = [];

if (!$files) { 
    echo json_encode([]); 
    exit; 
}

foreach ($files as $file) {
    if (!is_file($file)) continue;
    
    $content = file_get_contents($file);
    $title = 'Untitled'; 
    $date = 'Unknown Date'; 
    $tags = [];
    $markdown_body = $content;

    // Extract YAML frontmatter block
    if (preg_match('/^---\s*(.*?)\s*---\s*(.*)/s', $content, $matches)) {
        $frontmatter   = $matches[1];
        $markdown_body = $matches[2];
        
        // Improved parsing to handle more variations
        if (preg_match('/title:\s*"(.*?)"/', $frontmatter, $m)) {
            $title = $m[1];
        } elseif (preg_match('/title:\s*(.*)/', $frontmatter, $m)) {
            $title = trim($m[1], " '\"");
        }

        if (preg_match('/date:\s*"(.*?)"/', $frontmatter, $m)) {
            $date = $m[1];
        } elseif (preg_match('/date:\s*(.*)/', $frontmatter, $m)) {
            $date = trim($m[1], " '\"");
        }

        if (preg_match('/tags:\s*\[(.*?)\]/', $frontmatter, $m)) {
            $tags_string = str_replace(['"', "'"], '', $m[1]);
            $tags = array_map('trim', explode(',', $tags_string));
        }
    }

    $posts[] = [
        'id'          => md5(basename($file)),
        'slug'        => basename($file, '.md'),
        'title'       => $title,
        'date'        => $date,
        'tags'        => array_values(array_filter($tags)),
        'rawMarkdown' => trim($markdown_body),
    ];
}

// Sort by date descending (newest first)
usort($posts, function($a, $b) {
    $t1 = strtotime($a['date']);
    $t2 = strtotime($b['date']);
    return $t2 - $t1;
});

echo json_encode($posts);
?>
