<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderLabel;

class LabelMatchingService
{
    /**
     * Match a label to an existing order by detected Order ID scoped to user.
     */
    public function matchLabel(OrderLabel $label): array
    {
        if (empty($label->detected_order_id)) {
            $label->update([
                'status' => 'LABEL_ERROR',
                'confidence' => 0.00,
            ]);

            return [
                'success' => false,
                'status' => 'LABEL_ERROR',
                'message' => 'No Order ID detected in label.',
                'order' => null,
            ];
        }

        $userId = $label->user_id ?: auth()->id();

        $query = Order::where('order_id', strtoupper($label->detected_order_id));
        if ($userId) {
            $query->where('user_id', $userId);
        }
        $order = $query->first();

        if ($order) {
            $label->update([
                'order_id' => $order->id,
                'status' => 'LABEL_MATCHED',
                'confidence' => 100.00,
                'processed_at' => now(),
            ]);

            // Update order packing status if accepted
            $order->update([
                'packing_status' => 'READY',
            ]);

            return [
                'success' => true,
                'status' => 'LABEL_MATCHED',
                'message' => "Successfully matched to Order ID {$order->order_id}.",
                'order' => $order,
            ];
        }

        $label->update([
            'status' => 'LABEL_UNMATCHED',
            'confidence' => 50.00,
            'processed_at' => now(),
        ]);

        return [
            'success' => false,
            'status' => 'LABEL_UNMATCHED',
            'message' => "Detected Order ID {$label->detected_order_id} not found in database.",
            'order' => null,
        ];
    }
}
