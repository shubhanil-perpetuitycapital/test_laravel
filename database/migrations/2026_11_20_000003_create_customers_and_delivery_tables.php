<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Guest buyers, identified by mobile number. One row per person;
        // orders reference it instead of duplicating identity data.
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('mobile', 15)->unique();
            $table->string('email')->nullable()->index();
            $table->text('notes')->nullable();          // internal admin notes
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('delivery_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);                      // e.g. "Central Kolkata"
            $table->text('description')->nullable();
            $table->decimal('delivery_charge', 10, 2)->default(0);
            $table->decimal('free_delivery_threshold', 10, 2)->nullable();
            $table->boolean('same_day_available')->default(false);
            $table->time('same_day_cutoff')->nullable();      // e.g. 14:00
            $table->unsignedTinyInteger('lead_days')->default(1); // days to deliver when not same-day
            $table->string('estimated_text')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('delivery_pincodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_zone_id')->constrained()->cascadeOnDelete();
            $table->string('pincode', 6)->unique();
            $table->string('area_name', 120)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_pincodes');
        Schema::dropIfExists('delivery_zones');
        Schema::dropIfExists('customers');
    }
};
