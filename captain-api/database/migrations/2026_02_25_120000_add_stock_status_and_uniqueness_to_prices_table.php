<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prices', function (Blueprint $table) {
            $table->string('stock_status', 20)->default('unknown')->after('sell_price');
            $table->index(['product_id', 'vendor_id', 'scraped_at'], 'prices_product_vendor_scraped_idx');
            $table->unique(['vendor_id', 'product_id', 'scraped_at'], 'prices_vendor_product_scraped_unique');
        });
    }

    public function down(): void
    {
        Schema::table('prices', function (Blueprint $table) {
            $table->dropUnique('prices_vendor_product_scraped_unique');
            $table->dropIndex('prices_product_vendor_scraped_idx');
            $table->dropColumn('stock_status');
        });
    }
};
