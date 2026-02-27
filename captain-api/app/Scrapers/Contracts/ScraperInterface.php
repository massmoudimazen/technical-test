<?php

namespace App\Scrapers\Contracts;

interface ScraperInterface
{
    public function vendorSlug(): string;

    /**
     * @return array<int, array{
     *   product_slug: string,
     *   product_name?: string,
     *   metal?: string,
     *   buy_price: float|int,
     *   sell_price: float|int,
     *   stock_status?: string
     * }>
     */
    public function scrape(): array;
}
