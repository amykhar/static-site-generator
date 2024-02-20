<?php

namespace App\Command;

use App\Service\FileManagerService;
use App\Service\MarkdownParsingService;
use App\Service\SlugifyService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'parse-markdown', description: 'Convert markdown to html and save it to a file', )]
class ParseMarkdownCommand extends Command
{
    private array $metadata;

    public function __construct(
        private readonly string $markdownDirectory,
        private readonly string $htmlDirectory,
        private readonly string $assetsOutputDirectory,
        private readonly string $assetsInputDirectory,
        private readonly MarkdownParsingService $markdownParser,
        private readonly SlugifyService $slugifyService,
        private readonly FileManagerService $fileManagerService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $markdownFiles = $this->fileManagerService->getMarkdownFiles($this->markdownDirectory);
        foreach ($markdownFiles as $file) {
            $slug = str_replace('-md', '', $this->slugifyService->slugify($file));
            $fileContents = $this->fileManagerService->openFile($this->markdownDirectory.$file);
            $markdown = $this->parseMetadata($fileContents);
            $html = $this->markdownParser->parse($markdown, $this->assetsInputDirectory, $this->assetsOutputDirectory);
            $output = $this->prepareOutput($html);


            $this->fileManagerService->writeOutput($slug.'.html', $this->htmlDirectory, $output);
        }

        $io->success('All markdown files have been parsed and saved as html files.');

        return Command::SUCCESS;
    }

    private function prepareOutput(string $contents): string
    {
        $rootDir = getcwd();
        $output = $this->fileManagerService->openFile($rootDir.'/templates/output_template.html');
        $output = str_replace('BODY_GOES_HERE', $contents, $output);

        return $this->setTitle($output);
    }

    private function parseMetadata(string $contents): string
    {
        $contentArray = explode('---', $contents);
        $metadataLines = explode("\n", $contentArray[1]);
        foreach ($metadataLines as $line) {
            $line = trim($line);
            if ($line) {
                $data = explode(':', $line);
                $this->metadata[trim($data[0])] = trim($data[1]);
            }
        }

        return $contentArray[2];
    }

    private function setTitle(string $output): string
    {
        if (!array_key_exists('title', $this->metadata)) {
            return $output;
        }
        $title = $this->metadata['title'];
        if ($title) {
            $output = str_replace('TITLE_GOES_HERE', $title, $output);
        }

        return $output;
    }
}
