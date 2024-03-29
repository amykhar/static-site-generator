<?php

namespace App\Service;

readonly class FileManagerService
{
    public function __construct(private string $feedFile)
    {
    }

    /**
     * @return array<string>
     */
    public function getMarkdownFiles(string $directory): array
    {
        $files = scandir($directory);
        $markdownFiles = [];
        foreach ($files as $file) {
            if ('md' === pathinfo($file, PATHINFO_EXTENSION)) {
                $markdownFiles[] = $file;
            }
        }

        return $markdownFiles;
    }

    public function writeOutput(string $filename, string $htmlDirectory, string $contents): void
    {
        file_put_contents($htmlDirectory . $filename, $contents);
    }

    public function openFile(string $filename): string
    {
        return file_get_contents($filename);
    }

    public function copyFile(string $src, string $dest): void
    {
        if (!file_exists($src)) {
            return;
        }
        if (file_exists($dest)) {
            unlink($dest);
        }

        copy($src, $dest);
    }

    public function writeFeed(string $feedContent): void
    {
        file_put_contents($this->feedFile, $feedContent);
    }
}
