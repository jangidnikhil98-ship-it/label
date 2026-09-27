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
        // Resolve path if relative
        $resolvedPath = $filePath;
        if (!file_exists($resolvedPath)) {
            $storagePath = storage_path('app/public/' . ltrim($filePath, '/\\'));
            if (file_exists($storagePath)) {
                $resolvedPath = $storagePath;
            } else {
                $appPath = storage_path('app/' . ltrim($filePath, '/\\'));
                if (file_exists($appPath)) {
                    $resolvedPath = $appPath;
                }
            }
        }

        if (!file_exists($resolvedPath)) {
            Log::warning("OCR: Image file not found at {$filePath} (resolved: {$resolvedPath})");
            return '';
        }

        // 1. Built-in Windows Native OCR (Windows.Media.Ocr - zero install required)
        if (PHP_OS_FAMILY === 'Windows') {
            $scriptPath = __DIR__ . '/ocr.ps1';
            if (file_exists($scriptPath)) {
                $escapedScript = str_replace("'", "''", $scriptPath);
                $escapedImage = str_replace("'", "''", $resolvedPath);
                $cmd = "powershell -NoProfile -ExecutionPolicy Bypass -Command \"& '{$escapedScript}' -ImagePath '{$escapedImage}'\" 2>&1";
                $output = [];
                $ret = 0;
                @exec($cmd, $output, $ret);

                $text = trim(implode("\n", $output));
                if (!empty($text)) {
                    Log::info("OCR successfully extracted " . strlen($text) . " chars using Windows Native OCR.");
                    return $text;
                }
            }
        }

        // 2. Tesseract OCR if installed
        $tesseractPaths = [
            'tesseract',
            '/usr/bin/tesseract',
            '/usr/local/bin/tesseract',
            'C:\\Program Files\\Tesseract-OCR\\tesseract.exe',
            'C:\\Program Files (x86)\\Tesseract-OCR\\tesseract.exe',
        ];

        foreach ($tesseractPaths as $cmd) {
            $testCmd = (PHP_OS_FAMILY === 'Windows') ? "where {$cmd}" : "which {$cmd}";
            @exec($testCmd, $out, $ret);

            if ($ret === 0 || file_exists($cmd)) {
                $outputFile = tempnam(sys_get_temp_dir(), 'ocr_');
                $execCmd = "\"{$cmd}\" \"" . addslashes($resolvedPath) . "\" \"" . addslashes($outputFile) . "\" --oem 1 -l eng 2>&1";
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

        // 3. Binary metadata & EXIF stream fallback
        $content = @file_get_contents($resolvedPath);
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
