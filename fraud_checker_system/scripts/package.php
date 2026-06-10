<?php
/**
 * FraudGuard Pro - Distribution Packager
 */

$projectName = 'fraud_guard_pro_dist';
$distDir = __DIR__ . '/../../' . $projectName;
$sourceDir = __DIR__ . '/../';

echo "📦 Packaging FraudGuard Pro...\n";

// 1. Clean/Create Dist Directory
if (is_dir($distDir)) {
    echo "🧹 Cleaning existing dist directory...\n";
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($distDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($files as $fileinfo) {
        $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
        $todo($fileinfo->getRealPath());
    }
    rmdir($distDir);
}
mkdir($distDir);

// 2. Define Exclusions
$exclude = [
    '.git',
    '.env',
    'vendor', // Usually we'd run composer install --no-dev, but for simple dist we might exclude or include
    'storage/cache/*',
    'scripts',
    'composer.lock',
    '.gitignore'
];

echo "🚚 Copying files...\n";

// Use a simple recursive copy excluding specific items
function recursiveCopy($src, $dst, $exclude = []) {
    $dir = opendir($src);
    @mkdir($dst);
    while (false !== ($file = readdir($dir))) {
        if (($file != '.') && ($file != '..')) {
            $path = $src . '/' . $file;
            $isExcluded = false;
            foreach ($exclude as $pattern) {
                if (fnmatch($pattern, $file) || fnmatch($pattern, str_replace(__DIR__ . '/../', '', $path))) {
                    $isExcluded = true;
                    break;
                }
            }
            if ($isExcluded) continue;

            if (is_dir($path)) {
                recursiveCopy($path, $dst . '/' . $file, $exclude);
            } else {
                copy($path, $dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}

recursiveCopy($sourceDir, $distDir, $exclude);

echo "✅ Distribution folder created at: " . realpath($distDir) . "\n";
echo "Next Steps: Run 'composer install --no-dev' in the dist folder if you included vendor exclusions.\n";
