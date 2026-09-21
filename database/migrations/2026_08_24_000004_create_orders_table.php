<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_chat_id')->nullable()->constrained('whatsapp_chats')->nullOnDelete();
            $table->foreignId('whatsapp_message_id')->nullable()->constrained('whatsapp_messages')->nullOnDelete();
            $table->string('order_id')->unique()->index();
            $table->string('customer_name');
            $table->string('phone_number')->index();
            $table->string('status')->default('NEW')->index(); // NEW, VERIFIED, ACCEPTED, REJECTED, SHIPPED, COMPLETED
            $table->string('packing_status')->default('NOT_PACKED')->index(); // NOT_PACKED, READY, PACKED
            $table->string('source')->default('whatsapp_manual');
            $table->decimal('extraction_confidence', 5, 2)->default(100.00);
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('packed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
