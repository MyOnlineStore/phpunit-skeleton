#!/usr/bin/php -dphar.readonly=0
<?php
$srcRoot = realpath(__DIR__.'/vendor');
$buildRoot = realpath(__DIR__);
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($srcRoot, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
);

//foreach($iterator as $file){
//    var_dump($file->getFilename());
//}

echo "Build Symfony Console phar\n";
$phar = new Phar($buildRoot.'/phpunit-skeleton.phar', 0, 'phpunit-skeleton.phar');
$phar->buildFromIterator($iterator, $srcRoot);
$phar->setStub($phar::createDefaultStub('autoload.php'));
exit("Build complete\n");
