<?php

defined('ABSPATH') || exit;

global $product;

if (! $product || ! $product->is_visible()) {
    return;
}

echo view('woocommerce.content-product', [
    'product' => $product,
])->render();
