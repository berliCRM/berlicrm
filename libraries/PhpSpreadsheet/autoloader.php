<?php
spl_autoload_register(function ($class) {
    $prefixes = [
        'PhpOffice\\PhpSpreadsheet\\' => __DIR__ . '/',
        'Psr\\SimpleCache\\' => __DIR__ . '/Psr/',
        'Psr\\Container\\' => __DIR__ . '/Psr/',
        'ZipStream\\' => __DIR__ . '/ZipStream/',
    ];

    foreach ($prefixes as $prefix => $base_dir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            continue;
        }

        $relative_class = substr($class, $len);
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});