<?php

namespace App\Services;

use RuntimeException;

class ResumePdfGenerator
{
    private const PAGE_WIDTH = 612;
    private const PAGE_HEIGHT = 792;
    private const BOTTOM_MARGIN = 48;

    /**
     * @var list<string>
     */
    private array $pages = [];

    /**
     * @var array{accent: array{0: float, 1: float, 2: float}, margin: int, heading_size: int, body_size: int, line_spacing: int}
     */
    private array $style = [];

    private float $cursorY = 0;

    /**
     * Build a text-based PDF without storing or transmitting resume details.
     *
     * @param array<string, string> $resume
     */
    public function generate(array $resume, string $template): string
    {
        $this->style = $this->templateStyle($template);
        $this->pages = [''];
        $this->cursorY = self::PAGE_HEIGHT - $this->style['margin'];

        $nameLines = explode("\n", wordwrap($resume['name'], 42, "\n", true));
        foreach ($nameLines as $nameLine) {
            $this->addText($nameLine, 24, true, [0.09, 0.13, 0.20], 31);
        }

        $this->addText($resume['target_role'], 12, true, $this->style['accent'], 19);

        $contactDetails = array_filter([
            $resume['email'],
            $resume['phone'],
            $resume['location'],
            $resume['website'],
            $resume['linkedin'],
        ], fn (string $value): bool => trim($value) !== '');

        if ($contactDetails !== []) {
            $this->addParagraph(implode('  |  ', $contactDetails), 9, 12);
        }

        $this->addRule();

        foreach ([
            'SUMMARY' => $resume['summary'],
            'EXPERIENCE' => $resume['experience'],
            'EDUCATION' => $resume['education'],
            'SKILLS' => $resume['skills'],
            'CERTIFICATIONS' => $resume['certifications'],
        ] as $heading => $content) {
            if (trim($content) === '') {
                continue;
            }

            $this->addHeading($heading);
            $this->addParagraph($content, $this->style['body_size'], $this->style['line_spacing']);
            $this->cursorY -= 7;
        }

        return $this->buildDocument();
    }

    /**
     * @return array{accent: array{0: float, 1: float, 2: float}, margin: int, heading_size: int, body_size: int, line_spacing: int}
     */
    private function templateStyle(string $template): array
    {
        return match ($template) {
            'modern' => [
                'accent' => [0.24, 0.29, 0.72],
                'margin' => 52,
                'heading_size' => 11,
                'body_size' => 10,
                'line_spacing' => 13,
            ],
            'compact' => [
                'accent' => [0.05, 0.39, 0.39],
                'margin' => 42,
                'heading_size' => 10,
                'body_size' => 9,
                'line_spacing' => 11,
            ],
            'minimal' => [
                'accent' => [0.16, 0.20, 0.26],
                'margin' => 60,
                'heading_size' => 12,
                'body_size' => 10,
                'line_spacing' => 14,
            ],
            default => [
                'accent' => [0.12, 0.20, 0.32],
                'margin' => 54,
                'heading_size' => 11,
                'body_size' => 10,
                'line_spacing' => 13,
            ],
        };
    }

    private function addHeading(string $heading): void
    {
        $this->ensureSpace($this->style['heading_size'] + 18);

        [$red, $green, $blue] = $this->style['accent'];
        $fontSize = $this->style['heading_size'];
        $x = $this->style['margin'];
        $y = (int) $this->cursorY;

        $this->append(sprintf(
            '%.3f %.3f %.3f rg BT /F2 %d Tf 1 0 0 1 %d %d Tm (%s) Tj ET 0 0 0 rg',
            $red,
            $green,
            $blue,
            $fontSize,
            $x,
            $y,
            $this->escapePdfText($heading)
        ));

        $this->cursorY -= $fontSize + 7;
    }

    private function addRule(): void
    {
        $this->ensureSpace(15);

        [$red, $green, $blue] = $this->style['accent'];
        $x = $this->style['margin'];
        $endX = self::PAGE_WIDTH - $this->style['margin'];
        $y = (int) ($this->cursorY - 2);

        $this->append(sprintf(
            '%.3f %.3f %.3f RG 0.8 w %d %d m %d %d l S 0 0 0 RG',
            $red,
            $green,
            $blue,
            $x,
            $y,
            $endX,
            $y
        ));

        $this->cursorY -= 13;
    }

    private function addParagraph(string $content, int $fontSize, int $lineSpacing): void
    {
        $paragraphs = preg_split('/\R/u', trim($content)) ?: [];
        $maximumCharacters = $this->style['margin'] < 48 ? 108 : 96;

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if ($paragraph === '') {
                $this->cursorY -= 4;
                continue;
            }

            $isBullet = preg_match('/^[\s•\-*]/u', $paragraph) === 1;
            $paragraph = preg_replace('/^[\s•\-*]+/u', '', $paragraph) ?? $paragraph;
            $paragraph = $isBullet ? '- ' . $paragraph : $paragraph;
            $lines = explode("\n", wordwrap($paragraph, $maximumCharacters, "\n", true));

            foreach ($lines as $line) {
                $this->addText($line, $fontSize, false, [0.16, 0.19, 0.24], $lineSpacing);
            }
        }
    }

    /**
     * @param array{0: float, 1: float, 2: float} $color
     */
    private function addText(string $text, int $fontSize, bool $bold, array $color, int $spacing): void
    {
        $this->ensureSpace($spacing);

        [$red, $green, $blue] = $color;
        $font = $bold ? 'F2' : 'F1';
        $x = $this->style['margin'];
        $y = (int) $this->cursorY;

        $this->append(sprintf(
            '%.3f %.3f %.3f rg BT /%s %d Tf 1 0 0 1 %d %d Tm (%s) Tj ET 0 0 0 rg',
            $red,
            $green,
            $blue,
            $font,
            $fontSize,
            $x,
            $y,
            $this->escapePdfText($text)
        ));

        $this->cursorY -= $spacing;
    }

    private function ensureSpace(int $height): void
    {
        if ($this->cursorY - $height < self::BOTTOM_MARGIN) {
            $this->pages[] = '';
            $this->cursorY = self::PAGE_HEIGHT - $this->style['margin'];
        }
    }

    private function append(string $operation): void
    {
        $pageIndex = array_key_last($this->pages);
        $this->pages[$pageIndex] .= $operation . "\n";
    }

    private function escapePdfText(string $text): string
    {
        $encoded = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);
        if ($encoded === false) {
            $encoded = preg_replace('/[^\x20-\x7E]/', '', $text) ?? '';
        }

        $encoded = preg_replace('/[\x00-\x1F\x7F]/', ' ', $encoded) ?? '';

        return strtr($encoded, [
            '\\' => '\\\\',
            '(' => '\\(',
            ')' => '\\)',
        ]);
    }

    private function buildDocument(): string
    {
        $pageCount = count($this->pages);
        $regularFontId = 3 + ($pageCount * 2);
        $boldFontId = $regularFontId + 1;
        $objects = [];

        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $pageReferences = [];

        foreach ($this->pages as $index => $content) {
            $pageObjectId = 3 + ($index * 2);
            $contentObjectId = $pageObjectId + 1;
            $pageReferences[] = "{$pageObjectId} 0 R";
            $objects[$pageObjectId] = sprintf(
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %d %d] /Resources << /Font << /F1 %d 0 R /F2 %d 0 R >> >> /Contents %d 0 R >>',
                self::PAGE_WIDTH,
                self::PAGE_HEIGHT,
                $regularFontId,
                $boldFontId,
                $contentObjectId
            );
            $objects[$contentObjectId] = sprintf(
                "<< /Length %d >>\nstream\n%sendstream",
                strlen($content),
                $content
            );
        }

        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $pageReferences) . '] /Count ' . $pageCount . ' >>';
        $objects[$regularFontId] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objects[$boldFontId] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
        ksort($objects);

        $document = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $objectId => $object) {
            $offsets[$objectId] = strlen($document);
            $document .= "{$objectId} 0 obj\n{$object}\nendobj\n";
        }

        $crossReferenceOffset = strlen($document);
        $objectCount = max(array_keys($objects)) + 1;
        $document .= "xref\n0 {$objectCount}\n0000000000 65535 f \n";

        for ($objectId = 1; $objectId < $objectCount; $objectId++) {
            if (!isset($offsets[$objectId])) {
                throw new RuntimeException('The resume PDF contains an incomplete object table.');
            }

            $document .= sprintf("%010d 00000 n \n", $offsets[$objectId]);
        }

        $document .= "trailer\n<< /Size {$objectCount} /Root 1 0 R >>\n";
        $document .= "startxref\n{$crossReferenceOffset}\n%%EOF";

        return $document;
    }
}
