<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }

function infinityfree_runtime_files(): array {
    return [
        '.htaccess', 'index.php', 'item.php', 'item-form.php', 'delete.php',
        'register.php', 'login.php', 'logout.php', 'contact.php', 'messages.php', 'conversation.php',
        'assets/style.css', 'assets/app.js',
        'config/.htaccess', 'config/database.php', 'config/settings.php',
        'includes/.htaccess', 'includes/bootstrap.php', 'includes/auth.php',
        'includes/header.php', 'includes/footer.php', 'includes/icons.php', 'includes/messaging.php',
    ];
}

function build_infinityfree(string $root, string $destination): int {
    $files = infinityfree_runtime_files();
    $expected = array_merge($files, ['config/deployment.php']);
    $retired = ['assets/images/ruhuna-logo.png.png'];
    // Never accidentally preserve or overwrite credentials in a previous build.
    if (is_dir($destination)) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($destination, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen(rtrim($destination, '/\\')) + 1));
            if ($file->isLink() || ($file->isFile() && !in_array($relative, $expected, true) && !in_array($relative, $retired, true))) throw new RuntimeException('Build contains unexpected files. Use a clean output directory; do not package private configuration.');
        }
    }
    // Remove only known retired branding from the GENERATED build, never source assets.
    foreach ($retired as $relative) {
        if (is_file($destination . '/' . $relative) && !unlink($destination . '/' . $relative)) throw new RuntimeException('Cannot remove retired build asset.');
    }
    foreach ($files as $relative) {
        $source = $root . '/' . $relative;
        if (!is_file($source) || is_link($source)) throw new RuntimeException('Missing runtime asset: ' . $relative);
        $target = $destination . '/' . $relative;
        if (!is_dir(dirname($target)) && !mkdir(dirname($target), 0775, true)) throw new RuntimeException('Cannot create build directory.');
        if (!copy($source, $target)) throw new RuntimeException('Cannot copy runtime file.');
    }
    $origin = 'https://university-lost-and-found.infinityfreeapp.com';
    $marker = "<?php\ndeclare(strict_types=1);\nreturn " . var_export(['environment'=>'production','canonical_origin'=>$origin], true) . ";\n";
    if (file_put_contents($destination . '/config/deployment.php', $marker) === false) throw new RuntimeException('Cannot write production marker.');
    $rules = <<<'APACHE'

# Runtime-only package: configuration must never be served, even as source.
RewriteEngine On
RewriteRule ^(?:config|includes)(?:/|$) - [F,L,NC]

# Fixed trusted host. Enable a valid certificate before making the site public.
# GET/HEAD may redirect; insecure form POSTs reach PHP and are rejected with 426.
RewriteCond %{REQUEST_METHOD} ^(?:GET|HEAD)$
RewriteCond %{HTTPS} !=on [NC]
RewriteRule ^ https://university-lost-and-found.infinityfreeapp.com%{REQUEST_URI} [R=302,L]
APACHE;
    if (file_put_contents($destination . '/.htaccess', file_get_contents($root . '/.htaccess') . "\n" . $rules . "\n") === false) throw new RuntimeException('Cannot write deployment rules.');
    return count($expected);
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $root = dirname(__DIR__);
    if (!is_dir($root . '/build')) mkdir($root . '/build', 0775, true);
    file_put_contents($root . '/build/.htaccess', "Require all denied\n");
    $count = build_infinityfree($root, $root . '/build/infinityfree/htdocs');
    // Database setup instructions/schema stay OUTSIDE the upload directory.
    $schema = file_get_contents($root . '/database/schema.sql');
    $schema = preg_replace('/^(?:CREATE DATABASE[^\r\n]*|USE university_lost_found;)\r?\n/m', '', $schema);
    if (!is_dir(__DIR__ . '/infinityfree')) mkdir(__DIR__ . '/infinityfree', 0775, true);
    file_put_contents(__DIR__ . '/infinityfree/schema.sql', "-- Select the hosting database in phpMyAdmin first. Empty database only.\n" . $schema);
    echo "Built $count runtime files in build/infinityfree/htdocs. No private configuration or database export included.\n";
}
