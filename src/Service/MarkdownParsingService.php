<?php

namespace App\Service;

class MarkdownParsingService
{
    private int $sideNoteCount = 1;
    private string $output = '';

    public function __construct(
        private SlugifyService $slugifyService,
        private FileManagerService $fileManagerService
    ) {
    }

    public function parse(string $markdown, string $assetsInputDirectory, string $assetsOutputDirectory): string
    {
        $this->output = $markdown;
        $this->parseSections()
            ->parseImages($assetsInputDirectory, $assetsOutputDirectory)
            ->parseInternalLinks()
            ->parseHeaders()
            ->parseSideNotes()
            ->parseQuotes()
            ->parseNewThought()
            ->parseParagraphs()
            ->stripEmpty()
        ;

        return $this->output;
    }

    private function parseSections(): self
    {
        $pattern = '/\n{2}(.+)\n{2}/';
        $this->output = preg_replace_callback($pattern, function ($matches) {
            return "<section>\n" . trim($matches[1]) . "\n</section>";
        }, $this->output);

        return $this;
    }

    private function parseInternalLinks(): self
    {
        $pattern = '/\[\[(.+)]]/';

        $this->output = preg_replace_callback($pattern, function ($matches) {
            $filename = $this->slugifyService->slugify($matches[1]) . '.html';

            return '<a href="' . $filename . '">' . $matches[1] . '</a>';
        }, $this->output);

        return $this;
    }

    private function parseHeaders(): self
    {
        $pattern = '/(#+) (.+)/';

        $this->output = preg_replace_callback($pattern, function ($matches) {
            $level = strlen($matches[1]);
            $content = $matches[2];

            return '<h' . $level . '>' . $content . '</h' . $level . '>';
        }, $this->output);

        return $this;
    }

    private function parseSideNotes(): self
    {
        $pattern = '/>\[!sidenote]\n(>.+\n)+/';

        $this->output = preg_replace_callback($pattern, function ($matches) {
            $for = 'sidenote-' . $this->sideNoteCount++;
            $label = '<label class="margin-toggle sidenote-number" for="' . $for . '"></label>';

            return $label . '<input type="checkbox" class="margin-toggle" id="' .
                $for . '" /><span class="sidenote">' . str_replace('>', '', $matches[1]) . '</span>';
        }, $this->output);

        return $this;
    }

    private function parseQuotes(): self
    {
        $pattern = '/>(\[!quote]\n)(>.+\n)+/';
        $footerPattern = '/\((\w+|\s+)+\)/';
        $this->output = preg_replace_callback($pattern, function ($matches) use ($footerPattern) {
            $content = str_replace('>', '', $matches[0]);
            $content = str_replace('[!quote]', '', $content);
            preg_match($footerPattern, $content, $contents);
            if (!empty($contents)) {
                $attribution = str_replace(['(', ')'], '', $contents[0]);
                $content = preg_replace($footerPattern, '', $content);
            } else {
                $attribution = '';
            }
            $quote = "<div class='epigraph'>";
            $quote .= '<blockquote>';
            $quote .= '<p>' . $content . '</p>';
            if ($attribution) {
                $quote .= '<footer>';
                $quote .= $attribution . '</footnote>';
            }
            $quote .= '</blockquote></div>';

            return $quote;
        }, $this->output);

        return $this;
    }

    private function parseNewThought(): self
    {
        $pattern = '/--(.+?)--/';

        $this->output = preg_replace_callback($pattern, function ($matches) {
            return '<span class="newthought">' . $matches[1] . '</span>';
        }, $this->output);

        return $this;
    }

    private function parseParagraphs(): self
    {
        $pattern = '/\n(.+)/';
        $this->output = preg_replace_callback($pattern, function ($matches) {
            return '<p>' . trim($matches[1]) . '</p>';
        }, $this->output);

        return $this;
    }

    private function parseImages(string $assetsInputDirectory, string $assetsOutputDirectory): self
    {
        $pattern = '/!\[\[(.+)]]/';

        $this->output = preg_replace_callback(
            $pattern,
            function ($matches) use ($assetsInputDirectory, $assetsOutputDirectory) {
                $srcFileName = $assetsInputDirectory . $matches[1];
                $dstFileName = $assetsOutputDirectory . $matches[1];
                $this->fileManagerService->copyFile($srcFileName, $dstFileName);

                return '<figure><img src="/assets/images/' . $matches[1] . '"></figure>';
            },
            $this->output
        );

        return $this;
    }

    private function stripEmpty(): self
    {
        $this->output = str_replace('<p></p>', '', $this->output);

        return $this;
    }
}
