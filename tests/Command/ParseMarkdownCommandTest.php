<?php

namespace App\Tests\Command;

use App\Command\ParseMarkdownCommand;
use App\Exception\MissingMetadataException;
use App\Service\FileManagerService;
use App\Service\MarkdownParsingService;
use App\Service\SlugifyService;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * https://www.inanzzz.com/index.php/post/c7jb/testing-symfony-console-command-with-phpunit
 * @group command
 */
class ParseMarkdownCommandTest extends KernelTestCase
{
    use ProphecyTrait;

    private CommandTester $commandTester;

    /** @var ObjectProphecy<SlugifyService> */
    private ObjectProphecy $slugifyService;

    protected function setUp(): void
    {
        $markdownDirectory = $_ENV['MARKDOWN_DIRECTORY'] . 'missing-metadata/';
        $htmlDirectory = $_ENV['HTML_OUTPUT_DIRECTORY'];
        $assetsOutputDirectory = $_ENV['ASSETS_OUTPUT_DIRECTORY'];
        $assetsInputDirectory = $_ENV['MARKDOWN_ASSETS_DIRECTORY'];
        $markdownParser = $this->prophesize(MarkdownParsingService::class);
        $this->slugifyService = $this->prophesize(SlugifyService::class);
        $fileManagerService = new FileManagerService();

        $application = new Application();
        $application->add(new ParseMarkdownCommand(
            $markdownDirectory,
            $htmlDirectory,
            $assetsOutputDirectory,
            $assetsInputDirectory,
            $markdownParser->reveal(),
            $this->slugifyService->reveal(),
            $fileManagerService
        ));

        $command = $application->find('parse-markdown');
        $this->commandTester = new CommandTester($command);
    }

    public function testMissingMetadataException(): void
    {
        $this->slugifyService->slugify('missing-metadata.md')->willReturn('missing-metadata-md');
        $this->expectException(MissingMetadataException::class);
        $this->commandTester->execute([]);
    }
}
