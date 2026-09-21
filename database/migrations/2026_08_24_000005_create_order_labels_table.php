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
        Schema::create('order_labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_type'); // pdf, png, jpg, zip
            $table->string('detected_order_id')->nullable()->index();
            $table->decimal('confidence', 5, 2)->default(0.00);
            $table->string('status')->default('LABEL_PENDING')->index(); // LABEL_PENDING, LABEL_FOUND, LABEL_MATCHED, LABEL_READY, LABEL_PRINTED, LABEL_ERROR
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_labels');
    }
};
