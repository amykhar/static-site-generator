<?php

namespace App\Service;

class MarkdownParsingService
{
    private const START = 'START';

    private const SECTION_STARTED = 'SECTION_STARTED';

    private const PARAGRAPH_STARTED = 'PARAGRAPH_STARTED';

    private const PARAGRAPH_ENDED = 'PARAGRAPH_ENDED';

    private const SECTION_ENDED = 'SECTION_ENDED';

    private const LIST_STARTED = 'LIST_STARTED';

    private const QUOTE_STARTED = 'QUOTE_STARTED';

    private const SIDE_NOTE_STARTED = 'SIDE_NOTE_STARTED';
    private const CODE_BLOCK_STARTED = 'CODE_BLOCK_STARTED';
    private const CODE_BLOCK_ENDED = 'CODE_BLOCK_ENDED';

    private const START_PARAGRAPH = '<p>';
    private const END_PARAGRAPH = '</p>';
    private const START_SECTION = '<section>';
    private const END_SECTION = '</section>';

    private int $sideNoteCount = 1;
    /**
     * @var array<string>
     */
    private array $outputArray = [];
    private string $output = '';
    private string $input = '';
    private string $state = self::START;
    private int $newLineCount = 0;
    private string $quote = '';
    private string $sidenote = '';
    private string $assetsInputDirectory = '';
    private string $assetsOutputDirectory = '';
    private bool $inSection = false;

    /**
     * @var array<string>
     */
    private array $lines = [];

    public function __construct(
        private readonly SlugifyService $slugifyService,
        private readonly FileManagerService $fileManagerService,
    ) {
    }

    public function parse(string $markdown, string $assetsInputDirectory, string $assetsOutputDirectory): string
    {
        $this->reset();
        $this->input = $markdown;
        $this->assetsInputDirectory = $assetsInputDirectory;
        $this->assetsOutputDirectory = $assetsOutputDirectory;
        $this->lines = explode(PHP_EOL, $this->input);
        foreach ($this->lines as $line) {
            if (empty($line)) {
                $this->handleEmptyLine();
            } else {
                $this->parseLine($line);
            }
        }
        $this->finishDocument();

        return $this->output;
    }

    private function handleEmptyLine(): void
    {
        $this->newLineCount++;
        $current = trim(current($this->outputArray));

        switch ($this->state) {
            case self::SECTION_ENDED:
            case self::START:
                $this->state = self::SECTION_STARTED;
                $this->outputArray[] = self::START_SECTION;
                $this->inSection = true;
                break;
            case self::PARAGRAPH_STARTED:
                if (self::START_PARAGRAPH !== $current && '' !== $current) {
                    $this->state = self::PARAGRAPH_ENDED;
                    $this->outputArray[] = self::END_PARAGRAPH;
                }
                break;
            case self::PARAGRAPH_ENDED:
                $this->state = self::SECTION_ENDED;
                $this->outputArray[] = self::END_SECTION;
                $this->inSection = false;
                break;
            case self::LIST_STARTED:
                $this->state = self::PARAGRAPH_STARTED;
                $this->outputArray[] = '</ul>';
                $this->outputArray[] = self::START_PARAGRAPH;
                break;
            case self::SIDE_NOTE_STARTED:
                $this->sideNoteCount++;
                $this->sidenote = '';
                $this->state = self::PARAGRAPH_STARTED;
                $this->outputArray[] = '</span>';
                $this->outputArray[] = self::START_PARAGRAPH;
                break;
            case self::QUOTE_STARTED:
                $this->state = self::PARAGRAPH_STARTED;
                $this->outputArray[] = '</blockquote>';
                $this->outputArray[] = '</div>';
                $this->outputArray[] = self::START_PARAGRAPH;
                $this->quote = '';
                break;
            case self::CODE_BLOCK_ENDED:
                $this->state = self::PARAGRAPH_STARTED;
                $this->outputArray[] = '</pre>';
                $this->outputArray[] = self::START_PARAGRAPH;
                break;
            default:
                // do nothing
        }
    }

    private function parseLine(string $line): void
    {
        if ($this->state === self::START) {
            $this->state = self::SECTION_STARTED;
            $this->outputArray[] = self::START_SECTION;
            $this->inSection = true;
        }
        if ($this->state === self::SECTION_STARTED) {
            $this->state = 'PARAGRAPH_STARTED';
            $this->outputArray[] = self::START_PARAGRAPH;
        }
        $patterns = [
            '/(?<!\!)\[\[(.+?)\]\]/' => fn($matches) => $this->parseInternalLinks($matches),
            '/\[(.+)]\((http.+)\)/' => fn($matches) => $this->parseExternalLinks($matches),
            '/(#+) (.+)/' => fn($matches) => $this->parseHeaders($matches),
            '/\*{2}(.+)\*{2}/' => fn($matches) => $this->parseBold($matches),
            '/(?<!\*)\*([^*]+)\*(?!\*)/' => fn($matches) => $this->parseItalics($matches),
            '/^-\s+(.+)/' => fn($matches) => $this->parseUnorderedLists($matches),
            '/^>\[!sidenote]/' => fn($matches) => $this->parseSideNotes($matches),
            '/>\s+(.+)/' => fn($matches) => $this->parseSideNotes($matches),
            '/!\[\[(.+?)\]\]/' => fn($matches) => $this->parseImages($matches),
            '/^--(.+?)--$/' => fn($matches) => $this->parseNewThought($matches),
            '/^>\[!quote]/' => fn($matches) => $this->parseQuotes($matches),
            '/^```/' => fn() => $this->parseCodeBlock(),
        ];
        $matched = false;
        foreach ($patterns as $pattern => $callback) {
            if (preg_match($pattern, $line, $matches)) {
                if ($this->state === self::CODE_BLOCK_STARTED && $pattern !== '/^```/') {
                    $this->outputArray[] = $line;
                    return;
                }
                $matched = true;
                $output = preg_replace_callback($pattern, $callback, $line);
                if ($output !== '') {
                    $this->outputArray[] = $output;
                }
            }
        }

        if (!$matched) {
            if ($this->state === self::PARAGRAPH_ENDED) {
                $this->state = self::PARAGRAPH_STARTED;
                $this->outputArray[] = self::START_PARAGRAPH;
            } elseif ($this->state === self::SECTION_ENDED) {
                $this->state = self::PARAGRAPH_STARTED;
                $this->outputArray[] = self::START_SECTION;
                $this->inSection = true;
                $this->outputArray[] = self::START_PARAGRAPH;
            }
            $this->outputArray[] = $line;
        }
    }

    /**
     * @param array<string> $input
     * @return string
     */
    private function parseInternalLinks(array $input): string
    {
        $filename = $this->slugifyService->slugify($input[1]) . '.html';

        return '<a href="' . $filename . '">' . $input[1] . '</a>';
    }

    /**
     * @param array<string> $input
     * @return string
     */
    private function parseExternalLinks(array $input): string
    {
        return '<a href="' . $input[2] . '">' . $input[1] . '</a>';
    }

    /**
     * @param array<string> $input
     * @return string
     */
    private function parseHeaders(array $input): string
    {
        if ($this->state === self::PARAGRAPH_STARTED) {
            $this->state = self::PARAGRAPH_ENDED;
            $lastLine = array_pop($this->outputArray);
            if (self::START_PARAGRAPH !== $lastLine) {
                $this->outputArray[] = $lastLine . self::END_PARAGRAPH;
            }
        } elseif ($this->state === self::SECTION_STARTED) {
            $this->state = self::PARAGRAPH_ENDED;
        }
        $level = strlen($input[1]);
        $content = $input[2];

        $this->outputArray[] = '<h' . $level . '>' . $content . '</h' . $level . '>';

        return '';
    }

    /**
     * @param array<string> $input
     * @return string
     */
    public function parseUnorderedLists(array $input): string
    {
        if (self::LIST_STARTED !== $this->state) {
            $this->state = self::LIST_STARTED;
            $this->outputArray[] = '<ul>';
        }

        return '<li>' . trim($input[1]) . '</li>';
    }

    /**
     * @param array<string> $input
     * @return string
     */
    private function parseSideNotes(array $input): string
    {
        if (self::SIDE_NOTE_STARTED !== $this->state) {
            if (self::QUOTE_STARTED === $this->state) {
                return $this->parseQuotes($input);
            }
            $this->state = self::SIDE_NOTE_STARTED;
        }

        $for = 'sidenote-' . $this->sideNoteCount;

        if (empty($this->sidenote)) {
            $this->sidenote = self::START;

            return '<label class="margin-toggle sidenote-number" for="' . $for . '"></label>';
        }

        if (self::START === $this->sidenote) {
            $this->sidenote = 'IN_PROGRESS';

            return "<input type='checkbox' class='margin-toggle' id='" .
                $for . "' />\n<span class='sidenote'>\n" . $input[1];
        }

        return $input[1];
    }

    /**
     * @param array<string> $input
     * @return string
     */
    private function parseQuotes(array $input): string
    {
        if (self::QUOTE_STARTED !== $this->state) {
            if (self::SIDE_NOTE_STARTED === $this->state) {
                return $this->parseSideNotes($input);
            }
            $this->state = self::QUOTE_STARTED;
        }
        if (empty($this->quote)) {
            $this->quote = self::START;
            $this->outputArray[] = '<div class="epigraph">';
            $this->outputArray[] = '<blockquote>';
            $this->outputArray[] = self::START_PARAGRAPH;
        }
        if (self::START === $this->quote) {
            $this->quote = 'IN_PROGRESS';
            return '';
        }
        $footerPattern = '/\((\w+|\s+)+\)/';
        $content = str_replace('>', '', $input[1]);
        preg_match($footerPattern, $content, $contents);
        if (!empty($contents)) {
            $attribution = str_replace(['(', ')'], '', $contents[0]);
            $content = preg_replace($footerPattern, '', $content);
        } else {
            return $content;
        }
        if ($attribution) {
            $this->outputArray[] = self::END_PARAGRAPH;
            $this->outputArray[] = '<footer>';
            $this->outputArray[] = $attribution;
            $this->outputArray[] = '</footer>';
        }

        return '';
    }

/**
 * @param array<string> $input
 * @return string
 */
    private function parseNewThought(array $input): string
    {
        return '<span class="newthought">' . $input[1] . '</span>';
    }

/**
 * @param array<string> $input
 * @return string
 */
    private function parseImages(array $input): string
    {
        $combined = explode('|', $input[1]);
        $filename = trim($combined[0]);
        if (isset($combined[1])) {
            $alt = trim($combined[1]);
        } else {
            $alt = 'Unspecified image';
        }

        $srcFileName = $this->assetsInputDirectory . $filename;
        $dstFileName = $this->assetsOutputDirectory . $filename;
        $this->fileManagerService->copyFile($srcFileName, $dstFileName);
        $output = '<figure>' . PHP_EOL;
        $output .= '<img src="/assets/images/' . $filename . '" alt="' . $alt . '" />' . PHP_EOL;
        $output .= '</figure>';

        return $output;
    }

/**
 * @param array<string> $input
 * @return string
 */
    private function parseBold(array $input): string
    {
        return '<strong>' . $input[1] . '</strong>';
    }

/**
 * @param array<string> $input
 * @return string
 */
    private function parseItalics(array $input): string
    {
        return '<em>' . $input[1] . '</em>';
    }

    /**
     * @return string
     */
    private function parseCodeBlock(): string
    {
        if (self::CODE_BLOCK_STARTED !== $this->state) {
            $this->state = self::CODE_BLOCK_STARTED;
            $this->outputArray[] = '<pre>';
            return '';
        }
        $this->state = self::CODE_BLOCK_ENDED;
        return '';
    }

    private function finishDocument(): void
    {
        switch ($this->state) {
            case self::PARAGRAPH_STARTED:
                $lastLine = array_pop($this->outputArray);
                if ('' !== $lastLine && self::START_PARAGRAPH !== $lastLine) {
                    $this->outputArray[] = $lastLine;
                }
                if ($this->inSection) {
                    $this->outputArray[] = self::END_PARAGRAPH;
                    $this->outputArray[] = self::END_SECTION;
                    $this->inSection = false;
                }
                break;
            case self::SECTION_STARTED:
                array_pop($this->outputArray);
                $this->outputArray[] = self::END_SECTION;
                $this->inSection = false;
                break;
            case self::PARAGRAPH_ENDED:
                if ($this->inSection) {
                    $this->outputArray[] = self::END_SECTION;
                    $this->inSection = false;
                }
                break;
            default:
                // do nothing
        }
        $this->output = implode(PHP_EOL, $this->outputArray) . PHP_EOL;
    }

    private function reset(): void
    {
        $this->outputArray = [];
        $this->output = '';
        $this->input = '';
        $this->state = self::START;
        $this->newLineCount = 0;
        $this->quote = '';
        $this->sidenote = '';
        $this->sideNoteCount = 1;
        $this->inSection = false;
    }
}
