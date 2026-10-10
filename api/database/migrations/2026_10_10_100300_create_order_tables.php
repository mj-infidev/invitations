<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            // restrictOnDelete: removing a customer or an invitation must never erase sales history.
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('invitation_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('amount_paise'); // copied from the template at checkout, never from the browser
            $table->string('currency', 3)->default('INR');
            $table->enum('status', ['created', 'paid', 'failed', 'refunded'])->default('created')->index();
            $table->string('razorpay_order_id')->unique();
            $table->string('razorpay_payment_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        // Log of Razorpay webhooks. No foreign keys: an event is recorded even
        // when it names an order we don't know.
        Schema::create('payment_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->unique(); // stops the same webhook being processed twice
            $table->string('type'); // payment.captured and so on
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_events');
        Schema::dropIfExists('orders');
    }
};
