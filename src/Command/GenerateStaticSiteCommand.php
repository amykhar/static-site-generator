<?php

namespace App\Command;

use App\Exception\MissingMetadataException;
use App\Service\FeedCreatorService;
use App\Service\FileManagerService;
use App\Service\MarkdownParsingService;
use App\Service\SlugifyService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'generate-static-site', description: 'Convert markdown to html and generate rss feed',)]
class GenerateStaticSiteCommand extends Command
{
    /**
     * @var array<string, string>
     */
    private array $metadata;
    private ?string $currentFileName = null;

    public function __construct(
        private readonly string $markdownDirectory,
        private readonly string $htmlDirectory,
        private readonly string $assetsOutputDirectory,
        private readonly string $assetsInputDirectory,
        private readonly MarkdownParsingService $markdownParser,
        private readonly SlugifyService $slugifyService,
        private readonly FileManagerService $fileManagerService,
        private readonly FeedCreatorService $feedCreatorService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $markdownFiles = $this->fileManagerService->getMarkdownFiles($this->markdownDirectory);
        foreach ($markdownFiles as $file) {
            $this->currentFileName = $file;
            $io->success('Parsing ' . $file . '...');
            $slug = str_replace('-md', '', $this->slugifyService->slugify($file));
            $fileContents = $this->fileManagerService->openFile($this->markdownDirectory . $file);
            $markdown = $this->parseMetadata($fileContents);
            $html = $this->markdownParser->parse($markdown, $this->assetsInputDirectory, $this->assetsOutputDirectory);
            $output = $this->prepareOutput($html);
            $this->fileManagerService->writeOutput($slug . '.html', $this->htmlDirectory, $output);
            $this->feedCreatorService->addItem(
                $this->metadata['title'],
                $slug . '.html',
                $output,
                '2024-02-25T12:00:00+00:00'
            );
        }
        $io->success('Writing feed...');
        $this->feedCreatorService->write();

        $io->success('All markdown files have been parsed and saved as html files.');

        return Command::SUCCESS;
    }

    private function prepareOutput(string $contents): string
    {
        $rootDir = getcwd();
        $output = $this->fileManagerService->openFile($rootDir . '/templates/output_template.html');
        $output = str_replace('BODY_GOES_HERE', $contents, $output);

        return $this->setTitle($output);
    }

    private function parseMetadata(string $contents): string
    {
        $contentArray = explode('---', $contents);
        if (!isset($contentArray[1])) {
            throw new MissingMetadataException($this->currentFileName);
        }

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
