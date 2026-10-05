<?php

// Isolated worktree test bootstrap.
// Dependencies may be shared read-only with the live checkout, but all
// application, migration and test classes must resolve from THIS worktree.
require dirname(__DIR__).'/vendor/autoload.php';

$base = dirname(__DIR__);
spl_autoload_register(static function (string $class) use ($base): void {
    foreach ([
        'App\\' => 'app/',
        'Database\\' => 'database/',
        'Tests\\' => 'tests/',
    ] as $prefix => $directory) {
        if (! str_starts_with($class, $prefix)) {
            continue;
        }

        $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
        $file = $base.'/'.$directory.$relative.'.php';
        if (is_file($file)) {
            require $file;
        }
        return;
    }
}, true, true);
