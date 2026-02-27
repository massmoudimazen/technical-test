<?php

namespace App\Scrapers\Vendors;

use App\Scrapers\AbstractScraper;

class ExampleVendorScraper extends AbstractScraper
{
    public function vendorSlug(): string
    {
        return 'example-vendor';
    }

    public function scrape(): array
    {
        return [
            [
                'product_slug' => 'gold-bar-1kg',
                'product_name' => 'Gold Bar 1kg',
                'metal' => 'gold',
                'buy_price' => 64000,
                'sell_price' => 67500,
                'stock_status' => 'in_stock',
            ],
            [
                'product_slug' => 'silver-ounce',
                'product_name' => 'Silver Ounce',
                'metal' => 'silver',
                'buy_price' => 25,
                'sell_price' => 27,
                'stock_status' => 'out_of_stock',
            ],
        ];
    }
}
