<?php

namespace App\Jobs;

use App\Services\OrderExtractionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessWhatsAppMessage implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $rawText,
        public ?string $customerName = null,
        public ?string $imagePath = null
    ) {}

    public function handle(OrderExtractionService $extractionService): void
    {
        $result = $extractionService->processImport($this->rawText, $this->customerName, $this->imagePath);
        if ($result['parsed']['order_id'] && !$result['is_duplicate']) {
            $extractionService->createOrderFromExtraction($result);
        }
    }
}
