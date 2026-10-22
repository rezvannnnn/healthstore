<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (DB::connection()->getDriverName() !== 'sqlite') {
    throw new RuntimeException('This backup helper only supports the local SQLite database.');
}
$directory = storage_path('app/catalog-import/backups');
if (! is_dir($directory)) {
    mkdir($directory, 0775, true);
}
$path = $directory.'/before-import-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.sqlite';
DB::statement('VACUUM INTO ?', [$path]);
echo $path.PHP_EOL;
