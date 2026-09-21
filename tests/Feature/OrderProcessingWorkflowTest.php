<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderLabel;
use App\Models\User;
use App\Services\OrderExtractionService;
use App\Services\LabelMatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class OrderProcessingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Storage::fake('public');
    }

    public function test_extracts_order_and_phone_from_whatsapp_message(): void
    {
        $text = "Hello sir\nMy order id is 4089238472\nMobile: 9876543210\nPlease accept my order.";

        $response = $this->actingAs($this->user)
            ->postJson(route('whatsapp.import.process'), [
                'raw_text' => $text,
                'customer_name' => 'Rahul',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('orders', [
            'order_id' => '4089238472',
            'phone_number' => '9876543210',
            'customer_name' => 'Rahul',
            'status' => 'NEW',
        ]);
    }

    public function test_extracts_od_prefixed_order_ids_and_unicode_names(): void
    {
        $text = "[24/08/26, 10:48:59 AM] ~Anuj Choudhary: OD338440392393812100";

        $service = app(OrderExtractionService::class);
        $result = $service->parseMessageText($text);

        $this->assertEquals('OD338440392393812100', $result['order_id']);
    }

    public function test_detects_duplicate_order_id(): void
    {
        Order::create([
            'order_id' => 'ORD12345',
            'customer_name' => 'Amit',
            'phone_number' => '9812345678',
            'status' => 'NEW',
        ]);

        $text = "Order ID: ORD12345\nPhone: 9812345678";

        $response = $this->actingAs($this->user)
            ->postJson(route('whatsapp.import.process'), [
                'raw_text' => $text,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('is_duplicate', true);
    }

    public function test_label_matching_and_packing_mode_sequence(): void
    {
        // 1. Create order
        $order = Order::create([
            'order_id' => 'ORD55555',
            'customer_name' => 'Suresh',
            'phone_number' => '9876543210',
            'status' => 'ACCEPTED',
        ]);

        // 2. Create label with matching Order ID
        $label = OrderLabel::create([
            'file_path' => 'labels/test.pdf',
            'file_name' => 'ORD55555_label.pdf',
            'file_type' => 'pdf',
            'detected_order_id' => 'ORD55555',
            'status' => 'LABEL_PENDING',
        ]);

        $matchingService = app(LabelMatchingService::class);
        $result = $matchingService->matchLabel($label);

        $this->assertTrue($result['success']);
        $this->assertEquals('LABEL_MATCHED', $label->fresh()->status);
        $this->assertEquals('READY', $order->fresh()->packing_status);

        // 3. Mark as Packed in Packing Mode
        $response = $this->actingAs($this->user)
            ->postJson(route('packing.mark-packed', $order->id));

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertEquals('PACKED', $order->fresh()->packing_status);
        $this->assertEquals('SHIPPED', $order->fresh()->status);
    }

    public function test_whatsapp_zip_export_with_media_processing(): void
    {
        // Create temporary zip archive
        $zipPath = sys_get_temp_dir() . '/whatsapp_test_' . uniqid() . '.zip';
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE);
        
        $chatText = "[24/08/2026, 11:30:00 AM] ~Anuj Choudhary: OD338440392393812100 IMG-20260824-WA0001.jpg (file attached)";
        $zip->addFromString('_chat.txt', $chatText);
        $zip->addFromString('IMG-20260824-WA0001.jpg', 'dummy image content');
        $zip->close();

        $file = new UploadedFile($zipPath, 'WhatsApp Chat - Anuj.zip', 'application/zip', null, true);

        $response = $this->actingAs($this->user)
            ->postJson(route('whatsapp.import.process'), [
                'message_file' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('is_zip', true);

        $this->assertDatabaseHas('orders', [
            'order_id' => 'OD338440392393812100',
            'customer_name' => 'Anuj Choudhary',
        ]);

        @unlink($zipPath);
    }
}
