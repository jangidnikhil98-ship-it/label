<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class OcrService
{
    /**
     * Extract text from an image file path (screenshots, order photos, label images).
     */
    public function extractTextFromImage(string $filePath): string
    {
        if (!file_exists($filePath)) {
            return '';
        }

        // 1. Try Tesseract OCR if executable exists on system
        $tesseractPaths = [
            'tesseract',
            'C:\\Program Files\\Tesseract-OCR\\tesseract.exe',
            'C:\\Program Files (x86)\\Tesseract-OCR\\tesseract.exe',
        ];

        foreach ($tesseractPaths as $cmd) {
            $testCmd = (PHP_OS_FAMILY === 'Windows') ? "where {$cmd}" : "which {$cmd}";
            @exec($testCmd, $out, $ret);

            if ($ret === 0 || file_exists($cmd)) {
                $outputFile = tempnam(sys_get_temp_dir(), 'ocr_');
                $execCmd = "\"{$cmd}\" \"" . addslashes($filePath) . "\" \"" . addslashes($outputFile) . "\" --oem 1 -l eng 2>&1";
                @exec($execCmd);

                $txtFile = $outputFile . '.txt';
                if (file_exists($txtFile)) {
                    $text = file_get_contents($txtFile);
                    @unlink($outputFile);
                    @unlink($txtFile);
                    if (!empty(trim($text))) {
                        return $text;
                    }
                }
            }
        }

        // 2. Binary metadata & EXIF stream fallback
        $content = @file_get_contents($filePath);
        if (!$content) {
            return '';
        }

        preg_match_all('/[a-zA-Z0-9\s_\-\:]{6,}/', $content, $matches);
        if (!empty($matches[0])) {
            return implode(' ', $matches[0]);
        }

        return '';
    }
}
