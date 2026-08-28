<?php
$dir = __DIR__;
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($dir)
);

foreach ($files as $name => $file) {
    if (!$file->isDir() && $file->getExtension() === 'php' && basename($name) !== 'fix_paths.php') {
        $content = file_get_contents($name);
        $lines = explode("\n", $content);
        $modified = false;
        
        foreach ($lines as &$line) {
            if (strpos($line, 'BASE_URL') === false) {
                // only modify if not http/https
                if (strpos($line, 'href="http') === false && strpos($line, 'src="http') === false) {
                    $orig = $line;
                    $line = str_replace('href="/', 'href="<?= BASE_URL ?>/', $line);
                    $line = str_replace('action="/', 'action="<?= BASE_URL ?>/', $line);
                    $line = str_replace('src="/', 'src="<?= BASE_URL ?>/', $line);
                    if ($orig !== $line) {
                        $modified = true;
                    }
                }
            }
        }
        
        if ($modified) {
            file_put_contents($name, implode("\n", $lines));
            echo "Modified: " . str_replace($dir . DIRECTORY_SEPARATOR, '', $name) . "\n";
        }
    }
}
echo "Done.";
