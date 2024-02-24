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
        $expected = '<section>' . PHP_EOL;
        $expected .= '<h1>Header 1</h1>' . PHP_EOL;
        $expected .= '</section>' . PHP_EOL;
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
        $expected = '<section>' . PHP_EOL;
        $expected .= '<h2>Header 2</h2>' . PHP_EOL;
        $expected .= '</section>' . PHP_EOL;
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
        $expected = '<section>' . PHP_EOL;
        $expected .= '<h3>Header 3</h3>' . PHP_EOL;
        $expected .= '</section>' . PHP_EOL;
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
        $markdown = 'This line has a **bold** in the middle.';
        $expected = '<section>' . PHP_EOL;
        $expected .= '<p>' . PHP_EOL;
        $expected .= 'This line has a <strong>bold</strong> in the middle.' . PHP_EOL;
        $expected .= '</p>' . PHP_EOL;
        $expected .= '</section>' . PHP_EOL;
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
        $expected = '<section>' . PHP_EOL;
        $expected .= '<p>' . PHP_EOL;
        $expected .= '<em>italics</em>' . PHP_EOL;
        $expected .= '</p>' . PHP_EOL;
        $expected .= '</section>' . PHP_EOL;
        $this->assertEquals(
            $expected,
            $this->markdownParsingService->parse(
                $markdown,
                $this->assetsInputDirectory,
                $this->assetsOutputDirectory
            )
        );
    }

    public function testParseExternalLinks(): void
    {
        $markdown = '[Kagi](https://kagi.com)';
        $expected = '<section>' . PHP_EOL;
        $expected .= '<p>' . PHP_EOL;
        $expected .= '<a href="https://kagi.com">Kagi</a>' . PHP_EOL;
        $expected .= '</p>' . PHP_EOL;
        $expected .= '</section>' . PHP_EOL;
        $this->assertEquals(
            $expected,
            $this->markdownParsingService->parse(
                $markdown,
                $this->assetsInputDirectory,
                $this->assetsOutputDirectory
            )
        );

        $markdown = 'I need a link [Kagi](https://kagi.com) in the middle';
        $expected = '<section>' . PHP_EOL;
        $expected .= '<p>' . PHP_EOL;
        $expected .= 'I need a link <a href="https://kagi.com">Kagi</a> in the middle' . PHP_EOL;
        $expected .= '</p>' . PHP_EOL;
        $expected .= '</section>' . PHP_EOL;
        $this->assertEquals(
            $expected,
            $this->markdownParsingService->parse(
                $markdown,
                $this->assetsInputDirectory,
                $this->assetsOutputDirectory
            )
        );
    }

    public function testParseInternalLinks(): void
    {
        $markdown = '[[Testing This Link]]';
        $expected = '<section>' . PHP_EOL;
        $expected .= '<p>' . PHP_EOL;
        $expected .= '<a href="testing-this-link.html">Testing This Link</a>' . PHP_EOL;
        $expected .= '</p>' . PHP_EOL;
        $expected .= '</section>' . PHP_EOL;
        $this->assertEquals(
            $expected,
            $this->markdownParsingService->parse(
                $markdown,
                $this->assetsInputDirectory,
                $this->assetsOutputDirectory
            )
        );

        $markdown = 'I need a [[Link]] in the middle';
        $expected = '<section>' . PHP_EOL;
        $expected .= '<p>' . PHP_EOL;
        $expected .= 'I need a <a href="link.html">Link</a> in the middle' . PHP_EOL;
        $expected .= '</p>' . PHP_EOL;
        $expected .= '</section>' . PHP_EOL;
        $this->assertEquals(
            $expected,
            $this->markdownParsingService->parse(
                $markdown,
                $this->assetsInputDirectory,
                $this->assetsOutputDirectory
            )
        );
    }

    public function testUnorderedLists(): void
    {
        $markdown = $this->readFixture('unordered-lists.md');
        $actual = $this->markdownParsingService->parse(
            $markdown,
            $this->assetsInputDirectory,
            $this->assetsOutputDirectory
        );
        $expected = $this->readExpected('unordered-lists.html');
        $this->assertEquals($expected, $actual);
    }

    public function testSideNotes(): void
    {
        $markdown = $this->readFixture('sidenote.md');
        $actual = $this->markdownParsingService->parse(
            $markdown,
            $this->assetsInputDirectory,
            $this->assetsOutputDirectory
        );
        $expected = $this->readExpected('sidenote.html');
        $this->assertEquals($expected, $actual);

        $markdown = $this->readFixture('sidenote2.md');
        $actual = $this->markdownParsingService->parse(
            $markdown,
            $this->assetsInputDirectory,
            $this->assetsOutputDirectory
        );
        $expected2 = $this->readExpected('sidenote2.html');
        $this->assertEquals($expected2, $actual);
    }

    public function testParseImages(): void
    {
        $markdown = '![[computer_woman.png | Woman on laptop]]';
        $expected = '<section>' . PHP_EOL;
        $expected .= '<p>' . PHP_EOL;
        $expected .= '<figure>' . PHP_EOL;
        $expected .= '<img src="/assets/images/computer_woman.png" alt="Woman on laptop" />' . PHP_EOL;
        $expected .= '</figure>' . PHP_EOL;
        $expected .= '</p>' . PHP_EOL;
        $expected .= '</section>' . PHP_EOL;
        $this->assertEquals(
            $expected,
            $this->markdownParsingService->parse(
                $markdown,
                $this->assetsInputDirectory,
                $this->assetsOutputDirectory
            )
        );
    }

    public function testParseNewThought(): void
    {
        $markdown = '--I am-- in the process';
        $expected = '<section>' . PHP_EOL;
        $expected .= '<p>' . PHP_EOL;
        $expected .= '<span class="newthought">I am</span> in the process' . PHP_EOL;
        $expected .= '</p>' . PHP_EOL;
        $expected .= '</section>' . PHP_EOL;
        $this->assertEquals(
            $expected,
            $this->markdownParsingService->parse(
                $markdown,
                $this->assetsInputDirectory,
                $this->assetsOutputDirectory
            )
        );
    }

    public function testParseQuote(): void
    {
        $markdown = $this->readFixture('quote.md');
        $actual = $this->markdownParsingService->parse(
            $markdown,
            $this->assetsInputDirectory,
            $this->assetsOutputDirectory
        );
        $expected = $this->readExpected('quote.html');
        $this->assertEquals($expected, $actual);
    }

    public function testCodeBlock(): void
    {
        $markdown = $this->readFixture('code-block.md');
        $actual = $this->markdownParsingService->parse(
            $markdown,
            $this->assetsInputDirectory,
            $this->assetsOutputDirectory
        );
        $expected = $this->readExpected('code-block.html');
        $this->assertEquals($expected, $actual);
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
