<?php
header('Content-Type: application/rss+xml; charset=UTF-8');

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
        $description = '';
        $tags = [];
        $markdown_body = $content;

        $relativeDir = str_replace($content_dir, '', dirname($filePath));
        $rawCategory = trim($relativeDir, DIRECTORY_SEPARATOR);
        $category = $rawCategory ? str_replace(DIRECTORY_SEPARATOR, ' / ', $rawCategory) : 'Uncategorized';
        $slug = basename($filePath, '.md');

        if (preg_match('/^---\s*(.*?)\s*---\s*(.*)/s', $content, $matches)) {
            $frontmatter   = $matches[1];
            $markdown_body = $matches[2];

            if (preg_match('/title:\s*"(.*?)"/', $frontmatter, $m)) $title = $m[1];
            elseif (preg_match('/title:\s*(.*)/', $frontmatter, $m)) $title = trim($m[1], " '\"");

            if (preg_match('/slug:\s*"(.*?)"/', $frontmatter, $m)) $slug = $m[1];
            elseif (preg_match('/slug:\s*(.*)/', $frontmatter, $m)) $slug = trim($m[1], " '\"");

            if (preg_match('/date:\s*"(.*?)"/', $frontmatter, $m)) $date = $m[1];
            elseif (preg_match('/date:\s*(.*)/', $frontmatter, $m)) $date = trim($m[1], " '\"");

            if (preg_match('/description:\s*"(.*?)"/s', $frontmatter, $m)) $description = $m[1];
            elseif (preg_match('/description:\s*(.*)/', $frontmatter, $m)) $description = trim($m[1], " '\"");

            if (preg_match('/tags:\s*\[(.*?)\]/', $frontmatter, $m)) {
                $tags_string = str_replace(['"', "'"], '', $m[1]);
                $tags = array_map('trim', explode(',', $tags_string));
            }
        }

        $dateParts = explode('-', $date);
        $year  = $dateParts[0] ?? '0000';
        $month = $dateParts[1] ?? '00';
        $day   = $dateParts[2] ?? '00';
        $cleanCategory = strtolower(str_replace(' / ', '/', $category));
        $fullPath = "{$cleanCategory}/{$year}/{$month}/{$day}/{$slug}";

        $posts[] = [
            'slug'        => $slug,
            'path'        => $fullPath,
            'title'       => $title,
            'date'        => $date,
            'category'    => $category,
            'tags'        => array_values(array_filter($tags)),
            'description' => $description,
            'excerpt'     => $description ?: substr(strip_tags($markdown_body), 0, 300),
        ];
    }
}

usort($posts, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

$siteUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
         . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
$feedUrl = $siteUrl . '/rss.php';
$lastBuild = !empty($posts) ? date(DATE_RSS, strtotime($posts[0]['date'])) : date(DATE_RSS);

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
  <channel>
    <title>Humanity Last Blog</title>
    <link><?= htmlspecialchars($siteUrl) ?></link>
    <description>Share past experience of Rija on AI</description>
    <language>en-us</language>
    <lastBuildDate><?= $lastBuild ?></lastBuildDate>
    <atom:link href="<?= htmlspecialchars($feedUrl) ?>" rel="self" type="application/rss+xml"/>
<?php foreach (array_slice($posts, 0, 20) as $post): ?>
    <item>
      <title><?= htmlspecialchars($post['title']) ?></title>
      <link><?= htmlspecialchars($siteUrl) ?>#/<?= htmlspecialchars($post['path']) ?></link>
      <guid isPermaLink="true"><?= htmlspecialchars($siteUrl) ?>#/<?= htmlspecialchars($post['path']) ?></guid>
      <pubDate><?= date(DATE_RSS, strtotime($post['date'])) ?></pubDate>
      <category><?= htmlspecialchars($post['category']) ?></category>
      <description><?= htmlspecialchars($post['excerpt']) ?>...</description>
    </item>
<?php endforeach; ?>
  </channel>
</rss>
