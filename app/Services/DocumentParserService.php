<?php

namespace App\Services;

use Exception;
use Illuminate\Http\UploadedFile;
use Smalot\PdfParser\Parser as PdfParser;
use ZipArchive;

class DocumentParserService
{
    /**
     * Extract plain text and metadata from an absolute file path.
     *
     * @return array{text: string, word_count: int, page_count: int, is_scanned: bool}
     * @throws Exception
     */
    public function extractFromPath(string $filePath, ?string $originalName = null): array
    {
        if (!file_exists($filePath)) {
            throw new Exception("File not found on storage disk.");
        }

        $extension = strtolower(pathinfo($originalName ?: $filePath, PATHINFO_EXTENSION));

        $result = match ($extension) {
            'pdf' => $this->parsePdfFile($filePath),
            'docx' => $this->parseDocxFile($filePath),
            'txt', 'text', 'md' => $this->parseTextFile($filePath),
            default => throw new Exception("Unsupported file format [{$extension}]. We support PDF and DOCX files."),
        };

        $words = preg_split('/\s+/u', $result['text'], -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $wordCount = count($words);

        $isScanned = $wordCount < 30;

        return [
            'text' => $result['text'],
            'word_count' => $wordCount,
            'page_count' => $result['page_count'] ?? 1,
            'is_scanned' => $isScanned,
        ];
    }

    /**
     * Legacy helper for UploadedFile.
     *
     * @throws Exception
     */
    public function extractTextFromFile(UploadedFile $file): string
    {
        $parsed = $this->extractFromPath($file->getRealPath(), $file->getClientOriginalName());
        return $parsed['text'];
    }

    /**
     * Parse text and page count from PDF.
     *
     * @return array{text: string, page_count: int}
     * @throws Exception
     */
    protected function parsePdfFile(string $filePath): array
    {
        try {
            $parser = new PdfParser();
            $pdf = $parser->parseFile($filePath);
            $text = $pdf->getText();
            $pages = count($pdf->getPages());

            $cleaned = $this->cleanExtractedText($text);

            return [
                'text' => $cleaned,
                'page_count' => max(1, $pages),
            ];
        } catch (Exception $e) {
            throw new Exception("Failed to read PDF document: " . $e->getMessage());
        }
    }

    /**
     * Parse text from DOCX file.
     *
     * @return array{text: string, page_count: int}
     * @throws Exception
     */
    protected function parseDocxFile(string $filePath): array
    {
        if (!class_exists('ZipArchive')) {
            throw new Exception("PHP ZipArchive extension is required to read DOCX files.");
        }

        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new Exception("Unable to open DOCX archive.");
        }

        $xmlIndex = $zip->locateName('word/document.xml');
        if ($xmlIndex === false) {
            $zip->close();
            throw new Exception("Invalid DOCX document: word/document.xml not found.");
        }

        $xmlData = $zip->getFromIndex($xmlIndex);
        $zip->close();

        if (empty($xmlData)) {
            return ['text' => '', 'page_count' => 1];
        }

        // Replace paragraph and break tags with newlines
        $xmlData = preg_replace('/<\/w:p>/', "\n", $xmlData);
        $xmlData = preg_replace('/<w:br[^>]*>/', "\n", $xmlData);

        // Strip remaining XML tags
        $text = strip_tags($xmlData);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');

        $cleaned = $this->cleanExtractedText($text);

        // DOCX doesn't have explicit pages; estimate roughly 450 words per page
        $wordCount = str_word_count($cleaned);
        $pageEstimate = max(1, (int) ceil($wordCount / 450));

        return [
            'text' => $cleaned,
            'page_count' => $pageEstimate,
        ];
    }

    /**
     * Read plain text file.
     *
     * @return array{text: string, page_count: int}
     * @throws Exception
     */
    protected function parseTextFile(string $filePath): array
    {
        $content = @file_get_contents($filePath);
        if ($content === false) {
            throw new Exception("Failed to read text file.");
        }

        $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ASCII'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }

        $cleaned = $this->cleanExtractedText($content);
        $wordCount = str_word_count($cleaned);
        $pageEstimate = max(1, (int) ceil($wordCount / 450));

        return [
            'text' => $cleaned,
            'page_count' => $pageEstimate,
        ];
    }

    /**
     * Sanitize and normalize extracted text.
     */
    public function cleanExtractedText(string $text): string
    {
        // Normalize line breaks
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        // Remove non-printable control characters except newline and tab
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);

        // Replace 3+ consecutive newlines with 2 newlines
        $text = preg_replace("/\n{3,}/", "\n\n", $text);

        // Replace multiple horizontal spaces with a single space
        $text = preg_replace('/[ \t]{2,}/', ' ', $text);

        return trim($text);
    }
}