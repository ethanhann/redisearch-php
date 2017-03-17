<?php

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

require_once __DIR__ . '/../vendor/autoload.php';

$docsDir = __DIR__ . '/../docs';
$parsedown = new Parsedown();
$finder = (new Finder())->files()->in($docsDir)->name('*.md');
$template = file_get_contents(__DIR__ . '/template.html');

/** @var SplFileInfo $file */
foreach ($finder as $file) {
    $outputFile = "{$file->getPath()}/{$file->getBasename('.md')}.html";
    print "{$file->getPathname()} \033[34m-> \033[0m{$outputFile}" . PHP_EOL;
    file_put_contents(
        "{$file->getPath()}/{$file->getBasename('.md')}.html",
        str_replace('__CONTENT__', $parsedown->text($file->getContents()), $template)
    );
}

file_put_contents(
    $docsDir . '/README.html',
    str_replace('__CONTENT__', $parsedown->text(file_get_contents(__DIR__ . '/../README.md')), $template)
);
