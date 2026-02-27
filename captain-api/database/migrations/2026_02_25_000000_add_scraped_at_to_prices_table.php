<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add scraped_at timestamp for historical price tracking
     * Add indexes for query performance on historical data
     */
    public function up(): void
    {
        // Add scraped_at column
        Schema::table('prices', function (Blueprint $table) {
            $table->timestamp('scraped_at')->nullable()->after('sell_price');
            $table->index(['product_id', 'scraped_at'], 'prices_product_scraped_idx');
            $table->index(['vendor_id', 'scraped_at'], 'prices_vendor_scraped_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prices', function (Blueprint $table) {
            $table->dropIndex('prices_product_scraped_idx');
            $table->dropIndex('prices_vendor_scraped_idx');
            $table->dropColumn('scraped_at');
        });
    }
};

