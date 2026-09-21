<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportLabelRequest;
use App\Models\OrderLabel;
use App\Services\LabelProcessingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LabelController extends Controller
{
    public function __construct(
        protected LabelProcessingService $labelService
    ) {}

    public function index(Request $request)
    {
        $userId = auth()->id();
        $query = OrderLabel::where('user_id', $userId)->with('order');

        if ($request->filled('status')) {
            $query->where('status', strtoupper($request->status));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('detected_order_id', 'like', "%{$search}%")
                  ->orWhere('file_name', 'like', "%{$search}%");
            });
        }

        $labels = $query->latest()->paginate(15)->withQueryString();

        return view('labels.index', compact('labels'));
    }

    public function unmatched()
    {
        $userId = auth()->id();
        $labels = OrderLabel::where('user_id', $userId)->where('status', 'LABEL_UNMATCHED')->latest()->paginate(15);
        return view('labels.unmatched', compact('labels'));
    }

    public function importForm()
    {
        return view('labels.import');
    }

    public function processImport(ImportLabelRequest $request)
    {
        $userId = auth()->id();
        $files = $request->file('labels');
        $overall = [
            'processed' => 0,
            'matched' => 0,
            'unmatched' => 0,
            'errors' => 0,
            'labels' => [],
        ];

        foreach ($files as $file) {
            $res = $this->labelService->processUploadedFile($file, $userId);
            $overall['processed'] += $res['processed'];
            $overall['matched'] += $res['matched'];
            $overall['unmatched'] += $res['unmatched'];
            $overall['errors'] += $res['errors'];
            $overall['labels'] = array_merge($overall['labels'], $res['labels']);
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'summary' => $overall,
                'message' => "Processed {$overall['processed']} label(s): {$overall['matched']} matched, {$overall['unmatched']} unmatched.",
            ]);
        }

        return redirect()->route('labels.index')->with('success', "Processed {$overall['processed']} label(s): {$overall['matched']} matched, {$overall['unmatched']} unmatched.");
    }

    public function viewFile(OrderLabel $label)
    {
        if ($label->user_id && $label->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        $fullPath = Storage::disk('public')->path($label->file_path);
        if (!file_exists($fullPath)) {
            abort(404, 'Label file not found.');
        }

        $mime = Storage::disk('public')->mimeType($label->file_path) ?: 'application/octet-stream';
        return response()->file($fullPath, ['Content-Type' => $mime]);
    }

    public function downloadFile(OrderLabel $label)
    {
        if ($label->user_id && $label->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        $fullPath = Storage::disk('public')->path($label->file_path);
        if (!file_exists($fullPath)) {
            abort(404, 'Label file not found.');
        }

        return response()->download($fullPath, $label->file_name);
    }
}
