<?php
$dirs = ['admin', 'pelaku_usaha'];
foreach ($dirs as $dir) {
    $sidebar = ($dir === 'admin') ? 'sidebar_admin.php' : 'sidebar_pelaku_usaha.php';
    foreach (glob(__DIR__ . "/$dir/*.php") as $file) {
        $content = file_get_contents($file);
        
        $pattern_top = "/<div class=\"row\">\s*<div class=\"col-md-3\">\s*<button[^>]+>.*?<\/button>\s*<div[^>]+>\s*<\?php require_once __DIR__ \. '\/\.\.\/includes\/sidebar_[^.]+\.php'; \?>\s*<\/div>\s*<\/div>\s*<div class=\"col-md-9\">\s*/is";
        
        if (preg_match($pattern_top, $content)) {
            $replacement_top = "<?php require_once __DIR__ . '/../includes/$sidebar'; ?>\n<div class=\"app-content\">\n<button class=\"btn btn-sm btn-outline-secondary sidebar-toggle-btn\" type=\"button\">\n    ☰ Menu\n</button>\n\n";
            $content = preg_replace($pattern_top, $replacement_top, $content);
            
            $pattern_bottom = "/\s*<\/div>\s*<\/div>\s*(<\?php require_once __DIR__ \. '\/\.\.\/includes\/footer\.php'; \?>)/i";
            $content = preg_replace($pattern_bottom, "\n</div>\n\n$1", $content);
            
            file_put_contents($file, $content);
            echo "Updated " . basename($file) . "\n";
        } else {
            echo "Skipped " . basename($file) . " (no matching grid wrapper)\n";
        }
    }
}
echo "Done layout update.\n";
