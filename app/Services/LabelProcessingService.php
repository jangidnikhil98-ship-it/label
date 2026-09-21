<?php

namespace App\Services;

use App\Models\OrderLabel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser as PdfParser;
use ZipArchive;

class LabelProcessingService
{
    public function __construct(
        protected LabelMatchingService $matchingService,
        protected OcrService $ocrService
    ) {}

    /**
     * Extract Order ID from raw text.
     */
    public function extractOrderIdFromText(string $text): ?string
    {
        $patterns = [
            '/\b((?:OD|ORD|ORDER|MS|MEESHO|FK)[0-9A-Z]{5,22})\b/i',
            '/(?:order\s*id|order\s*no|ord|id)[\s\:\-\#]*([a-zA-Z0-9]{6,20})/i',
            '/\b([0-9]{9,20})\b/', // Meesho numeric Order ID (e.g. 4089238472)
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $candidate = trim($matches[1]);
                if (strlen($candidate) < 10 || !preg_match('/^[6-9]\d{9}$/', $candidate)) {
                    return strtoupper($candidate);
                }
            }
        }

        return null;
    }

    /**
     * Process an uploaded file (PDF, ZIP, JPG, PNG).
     */
    public function processUploadedFile(UploadedFile $file, ?int $userId = null): array
    {
        $userId = $userId ?: auth()->id();
        $mimeType = $file->getMimeType();
        $extension = strtolower($file->getClientOriginalExtension());
        $originalName = $file->getClientOriginalName();

        $results = [
            'processed' => 0,
            'matched' => 0,
            'unmatched' => 0,
            'errors' => 0,
            'labels' => [],
        ];

        if ($extension === 'zip') {
            return $this->processZipFile($file, $userId);
        }

        $path = $file->store('labels', 'public');
        $fullPath = Storage::disk('public')->path($path);

        $detectedOrderId = null;
        $extractedText = '';

        if ($extension === 'pdf') {
            try {
                $parser = new PdfParser();
                $pdf = $parser->parseFile($fullPath);
                $pages = $pdf->getPages();

                // If PDF has multiple pages (batch labels PDF), process each page
                if (count($pages) > 1) {
                    foreach ($pages as $index => $page) {
                        $pageText = $page->getText();
                        $pageOrderId = $this->extractOrderIdFromText($pageText);

                        $label = OrderLabel::create([
                            'user_id' => $userId,
                            'file_path' => $path,
                            'file_name' => $originalName . ' (Page ' . ($index + 1) . ')',
                            'file_type' => 'pdf',
                            'detected_order_id' => $pageOrderId,
                            'confidence' => $pageOrderId ? 100.00 : 0.00,
                            'status' => 'LABEL_PENDING',
                        ]);

                        $matchResult = $this->matchingService->matchLabel($label);
                        $results['processed']++;
                        if ($matchResult['success']) {
                            $results['matched']++;
                        } else {
                            $results['unmatched']++;
                        }
                        $results['labels'][] = $label->fresh();
                    }

                    return $results;
                } else {
                    $extractedText = $pdf->getText();
                    $detectedOrderId = $this->extractOrderIdFromText($extractedText);
                }
            } catch (\Exception $e) {
                // Fallback to OCR text search if PDF parser fails
                $extractedText = $this->ocrService->extractTextFromImage($fullPath);
                $detectedOrderId = $this->extractOrderIdFromText($extractedText);
            }
        } else {
            // Image file (jpg, png, jpeg)
            $extractedText = $this->ocrService->extractTextFromImage($fullPath);
            $detectedOrderId = $this->extractOrderIdFromText($extractedText);
        }

        $label = OrderLabel::create([
            'user_id' => $userId,
            'file_path' => $path,
            'file_name' => $originalName,
            'file_type' => $extension,
            'detected_order_id' => $detectedOrderId,
            'confidence' => $detectedOrderId ? 100.00 : 0.00,
            'status' => 'LABEL_PENDING',
        ]);

        $matchResult = $this->matchingService->matchLabel($label);

        $results['processed'] = 1;
        if ($matchResult['success']) {
            $results['matched'] = 1;
        } else {
            $results['unmatched'] = 1;
        }
        $results['labels'][] = $label->fresh();

        return $results;
    }

    /**
     * Process ZIP file archive containing shipping labels.
     */
    protected function processZipFile(UploadedFile $zipFile, ?int $userId = null): array
    {
        $userId = $userId ?: auth()->id();
        $results = [
            'processed' => 0,
            'matched' => 0,
            'unmatched' => 0,
            'errors' => 0,
            'labels' => [],
        ];

        $zip = new ZipArchive();
        if ($zip->open($zipFile->getRealPath()) === true) {
            $tempFolder = 'labels/unzipped_' . uniqid();
            $extractPath = Storage::disk('public')->path($tempFolder);

            $zip->extractTo($extractPath);
            $zip->close();

            $files = Storage::disk('public')->allFiles($tempFolder);
            foreach ($files as $fileRelativePath) {
                $ext = strtolower(pathinfo($fileRelativePath, PATHINFO_EXTENSION));
                if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'])) {
                    $fullPath = Storage::disk('public')->path($fileRelativePath);
                    $fileName = basename($fileRelativePath);

                    $detectedOrderId = null;
                    if ($ext === 'pdf') {
                        try {
                            $parser = new PdfParser();
                            $pdf = $parser->parseFile($fullPath);
                            $text = $pdf->getText();
                            $detectedOrderId = $this->extractOrderIdFromText($text);
                        } catch (\Exception $e) {
                            $text = $this->ocrService->extractTextFromImage($fullPath);
                            $detectedOrderId = $this->extractOrderIdFromText($text);
                        }
                    } else {
                        $text = $this->ocrService->extractTextFromImage($fullPath);
                        $detectedOrderId = $this->extractOrderIdFromText($text);
                    }

                    $label = OrderLabel::create([
                        'user_id' => $userId,
                        'file_path' => $fileRelativePath,
                        'file_name' => $fileName,
                        'file_type' => $ext,
                        'detected_order_id' => $detectedOrderId,
                        'confidence' => $detectedOrderId ? 100.00 : 0.00,
                        'status' => 'LABEL_PENDING',
                    ]);

                    $matchResult = $this->matchingService->matchLabel($label);
                    $results['processed']++;
                    if ($matchResult['success']) {
                        $results['matched']++;
                    } else {
                        $results['unmatched']++;
                    }
                    $results['labels'][] = $label->fresh();
                }
            }
        } else {
            $results['errors']++;
        }

        return $results;
    }
}
