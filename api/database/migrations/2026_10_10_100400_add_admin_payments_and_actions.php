<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // A payment marked by hand has no Razorpay order.
            $table->string('razorpay_order_id')->nullable()->change();
            // Null for a Razorpay payment; the admin who marked it paid otherwise.
            $table->foreignId('paid_by_admin_id')->nullable()->after('paid_at')->constrained('admins')->restrictOnDelete();
        });

        // Every admin action, as it happened. Written by App\Support\AdminLog in the
        // same transaction as the change; the app never edits or deletes a row.
        Schema::create('admin_actions', function (Blueprint $table) {
            $table->id();
            // Null for a failed sign-in (details.email says who was tried).
            $table->foreignId('admin_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('action', 50)->index(); // invitation.update, order.mark_paid, admin.login …
            $table->string('subject_type', 50)->nullable(); // invitation, order, template, media
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->text('reason')->nullable();
            $table->json('details')->nullable(); // { changes: { field: [before, after] }, … }
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_actions');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('paid_by_admin_id');
            $table->string('razorpay_order_id')->nullable(false)->change();
        });
    }
};
