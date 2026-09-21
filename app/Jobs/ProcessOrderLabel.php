<?php

namespace App\Jobs;

use App\Models\OrderLabel;
use App\Services\LabelMatchingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessOrderLabel implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public OrderLabel $label
    ) {}

    public function handle(LabelMatchingService $matchingService): void
    {
        $matchingService->matchLabel($this->label);
    }
}
