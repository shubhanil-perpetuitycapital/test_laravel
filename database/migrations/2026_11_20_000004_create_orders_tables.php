<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('description')->nullable();
            $table->string('type', 10)->default('fixed');        // fixed | percent
            $table->decimal('value', 10, 2);
            $table->decimal('min_order_amount', 10, 2)->nullable();
            $table->decimal('max_discount', 10, 2)->nullable();  // cap for percent coupons
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 30)->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();

            $table->string('status', 30)->default('pending')->index();
            $table->string('payment_method', 20);               // upi | cod
            $table->string('payment_status', 20)->default('unpaid')->index(); // unpaid | submitted | paid | refunded
            $table->string('payment_reference', 100)->nullable();
            $table->string('payment_screenshot')->nullable();   // private disk path

            // Delivery address snapshot: where THIS order went (an address can change between orders).
            $table->string('delivery_method', 20);             // local | outstation
            $table->foreignId('delivery_zone_id')->nullable()->constrained()->nullOnDelete();
            $table->string('recipient_name', 120);
            $table->string('recipient_mobile', 15);
            $table->string('address_line', 500);
            $table->string('city', 80);
            $table->string('state', 80);
            $table->string('pincode', 6)->index();
            $table->string('delivery_instructions', 500)->nullable();
            $table->text('order_notes')->nullable();
            $table->date('expected_delivery_date')->nullable();

            $table->decimal('subtotal', 10, 2);
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->string('coupon_code', 40)->nullable();
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('delivery_charge', 10, 2)->default(0);
            $table->boolean('delivery_charge_pending')->default(false); // outstation: admin confirms charge
            $table->decimal('total', 10, 2);

            $table->string('courier_name', 100)->nullable();
            $table->string('tracking_number', 100)->nullable();
            $table->text('internal_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('created_at');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            // Snapshots so historical orders stay accurate after product edits.
            $table->string('product_name', 180);
            $table->string('sku', 60)->nullable();
            $table->string('weight', 60)->nullable();
            $table->decimal('unit_price', 10, 2);
            $table->unsignedInteger('quantity');
            $table->decimal('line_total', 10, 2);
            $table->timestamps();
        });

        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->text('comment')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('custom_order_enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('enquiry_number', 30)->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 120);
            $table->string('mobile', 15)->index();
            $table->string('email')->nullable();
            $table->string('occasion', 100)->nullable();
            $table->string('chocolate_type', 100)->nullable();
            $table->string('quantity', 100);
            $table->string('budget', 100)->nullable();
            $table->date('required_date')->nullable();
            $table->string('delivery_pincode', 6)->nullable();
            $table->text('message')->nullable();
            $table->string('reference_image')->nullable();
            $table->string('status', 20)->default('new')->index(); // new | contacted | quoted | converted | closed
            $table->decimal('quoted_amount', 10, 2)->nullable();
            $table->text('quotation_notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->foreignId('converted_order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_order_enquiries');
        Schema::dropIfExists('order_status_histories');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('coupons');
    }
};
