<?php

namespace App\Tests\Service;

use App\Service\FileManagerService;
use App\Service\MarkdownParsingService;
use App\Service\SlugifyService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MarkdownParsingServiceTest extends KernelTestCase
{
    private MarkdownParsingService $markdownParsingService;
    private string $assetsInputDirectory;
    private string $assetsOutputDirectory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markdownParsingService = new MarkdownParsingService(
            new SlugifyService(),
            new FileManagerService()
        );
        $this->assetsInputDirectory = $_ENV['ASSETS_OUTPUT_DIRECTORY'];
        $this->assetsOutputDirectory = $_ENV['ASSETS_OUTPUT_DIRECTORY'];
    }

    public function testParseH1(): void
    {
        $markdown = '# Header 1';
        $expected = '<h1>Header 1</h1>';
        $this->assertEquals(
            $expected,
            $this->markdownParsingService->parse(
                $markdown,
                $this->assetsInputDirectory,
                $this->assetsOutputDirectory
            )
        );
    }

    public function testParseH2(): void
    {
        $markdown = '## Header 2';
        $expected = '<h2>Header 2</h2>';
        $this->assertEquals(
            $expected,
            $this->markdownParsingService->parse(
                $markdown,
                $this->assetsInputDirectory,
                $this->assetsOutputDirectory
            )
        );
    }

    public function testParseH3(): void
    {
        $markdown = '### Header 3';
        $expected = '<h3>Header 3</h3>';
        $this->assertEquals(
            $expected,
            $this->markdownParsingService->parse(
                $markdown,
                $this->assetsInputDirectory,
                $this->assetsOutputDirectory
            )
        );
    }

    public function testParseMultipleHeaders(): void
    {
        $markdown = $this->readFixture('multiple-headers.md');
        $actual = $this->markdownParsingService->parse(
            $markdown,
            $this->assetsInputDirectory,
            $this->assetsOutputDirectory
        );
        $expected = $this->readExpected('multiple-headers.html');
        $this->assertEquals($expected, $actual);
    }

    public function testParseBold(): void
    {
        $markdown = '**bold**';
        $expected = '<strong>bold</strong>';
        $this->assertEquals(
            $expected,
            $this->markdownParsingService->parse(
                $markdown,
                $this->assetsInputDirectory,
                $this->assetsOutputDirectory
            )
        );
    }

    public function testParseItalic(): void
    {
        $markdown = '*italics*';
        $expected = '<em>italics</em>';
        $this->assertEquals(
            $expected,
            $this->markdownParsingService->parse(
                $markdown,
                $this->assetsInputDirectory,
                $this->assetsOutputDirectory
            )
        );
    }

    private function readFixture(string $filename): string
    {
        return file_get_contents(
            $_ENV['MARKDOWN_DIRECTORY'] . $filename
        );
    }

    private function readExpected(string $filename): string
    {
        return file_get_contents(
            $_ENV['EXPECTED_DIRECTORY'] . $filename
        );
    }
}
