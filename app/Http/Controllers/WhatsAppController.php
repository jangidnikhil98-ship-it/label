<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportMessageRequest;
use App\Models\WhatsAppChat;
use App\Models\WhatsAppMessage;
use App\Services\OrderExtractionService;
use Illuminate\Http\Request;

class WhatsAppController extends Controller
{
    public function __construct(
        protected OrderExtractionService $extractionService
    ) {}

    public function chats(Request $request)
    {
        $userId = auth()->id();
        $query = WhatsAppChat::where('user_id', $userId)->withCount('messages')->with('orders');

        if ($request->filled('period')) {
            $period = strtolower($request->period);
            if ($period === 'today') {
                $query->whereDate('created_at', date('Y-m-d'));
            } elseif ($period === 'yesterday') {
                $query->whereDate('created_at', date('Y-m-d', strtotime('-1 day')));
            } elseif ($period === '3days') {
                $query->whereDate('created_at', '>=', date('Y-m-d', strtotime('-3 days')));
            } elseif ($period === '7days') {
                $query->whereDate('created_at', '>=', date('Y-m-d', strtotime('-7 days')));
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $chats = $query->latest()->paginate(15)->withQueryString();
        return view('whatsapp.index', compact('chats'));
    }

    public function messages(Request $request)
    {
        $userId = auth()->id();
        $query = WhatsAppMessage::whereHas('chat', fn($q) => $q->where('user_id', $userId))->with(['chat', 'order']);

        if ($request->filled('chat_id')) {
            $query->where('whatsapp_chat_id', $request->chat_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $messages = $query->latest()->paginate(20)->withQueryString();
        return view('whatsapp.messages', compact('messages'));
    }

    public function importForm()
    {
        return view('whatsapp.import');
    }

    public function processImport(ImportMessageRequest $request)
    {
        $userId = auth()->id();
        $rawText = $request->input('raw_text', '');
        $customerName = $request->input('customer_name');
        $imagePath = null;
        $isJson = $request->ajax() || $request->wantsJson();

        if ($request->hasFile('message_file')) {
            $file = $request->file('message_file');
            $ext = strtolower($file->getClientOriginalExtension());

            if ($ext === 'zip') {
                $zipResult = $this->extractionService->processWhatsAppZipExport($file, $userId);

                if (!empty($zipResult['error'])) {
                    if ($isJson) {
                        return response()->json(['success' => false, 'message' => $zipResult['error']], 422);
                    }
                    return redirect()->back()->with('error', $zipResult['error']);
                }

                if ($isJson) {
                    return response()->json([
                        'success' => true,
                        'is_zip' => true,
                        'message' => "Successfully processed WhatsApp Chat Export! Created {$zipResult['orders_created']} order(s), {$zipResult['duplicates_count']} duplicate(s) skipped.",
                        'summary' => $zipResult,
                    ]);
                }

                return redirect()->route('orders.index')->with('success', "Processed WhatsApp Chat Export! Created {$zipResult['orders_created']} order(s).");
            } elseif ($ext === 'txt') {
                $rawText = file_get_contents($file->getRealPath());
                $txtResult = $this->extractionService->processWhatsAppTxtExport($rawText, $userId);

                if ($isJson) {
                    return response()->json([
                        'success' => true,
                        'is_zip' => true,
                        'message' => "Successfully processed WhatsApp TXT Export! Created {$txtResult['orders_created']} order(s), {$txtResult['duplicates_count']} duplicate(s) skipped.",
                        'summary' => $txtResult,
                    ]);
                }

                return redirect()->route('orders.index')->with('success', "Processed WhatsApp TXT Export! Created {$txtResult['orders_created']} order(s).");
            } elseif ($ext === 'pdf') {
                // PDF document upload support
                $pdfPath = $file->store('whatsapp_imports', 'public');
                $fullPath = storage_path('app/public/' . $pdfPath);
                $pdfText = app(\App\Services\OcrService::class)->extractTextFromImage($fullPath);
                if (!empty($pdfText)) {
                    $rawText = $pdfText;
                }
            } else {
                $imagePath = $file->store('whatsapp_imports', 'public');
                $fullPath = storage_path('app/public/' . $imagePath);
                $imageText = app(\App\Services\OcrService::class)->extractTextFromImage($fullPath);
                if (!empty($imageText)) {
                    $rawText = $imageText;
                }
            }
        }

        if (empty(trim($rawText)) && !$imagePath) {
            if ($isJson) {
                return response()->json(['success' => false, 'message' => 'Please provide message text or upload a file.'], 422);
            }
            return redirect()->back()->with('error', 'Please provide message text or upload a file.');
        }

        $extractionResult = $this->extractionService->processImport($rawText, $customerName, $imagePath ? storage_path('app/public/' . $imagePath) : null, $userId);

        if (!$extractionResult['parsed']['order_id']) {
            if ($isJson) {
                return response()->json([
                    'success' => false,
                    'message' => 'Could not detect a valid Order ID from the message text.',
                    'extraction' => $extractionResult,
                ], 422);
            }
            return redirect()->back()->with('error', 'Could not detect a valid Order ID from the message.');
        }

        if ($extractionResult['is_duplicate'] && !$request->boolean('confirm_duplicate')) {
            if ($isJson) {
                return response()->json([
                    'success' => false,
                    'is_duplicate' => true,
                    'message' => 'This Order ID already exists.',
                    'existing_order' => $extractionResult['existing_order'],
                    'extraction' => $extractionResult,
                ]);
            }
        }

        $order = $this->extractionService->createOrderFromExtraction($extractionResult);

        if ($isJson) {
            return response()->json([
                'success' => true,
                'is_zip' => false,
                'message' => "Order {$order->order_id} extracted and created successfully!",
                'order' => $order->load('chat'),
                'redirect' => route('orders.show', $order->id),
            ]);
        }

        return redirect()->route('orders.show', $order->id)->with('success', "Order {$order->order_id} created successfully!");
    }
}
