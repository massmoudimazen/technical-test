@php
  if (!isset($product) || !$product instanceof WC_Product) {
    return;
  }

  $productId = $product->get_id();
  $priceHtml = $product->get_price_html();
  $shortDescription = trim((string) $product->get_short_description());

  if ($shortDescription === '') {
    $shortDescription = wp_trim_words(wp_strip_all_tags((string) $product->get_description()), 20);
  }

  $metalTerms = [];
  $metalSlugs = [];

  if (taxonomy_exists('pa_metal')) {
    $terms = get_the_terms($productId, 'pa_metal');

    if (!is_wp_error($terms) && !empty($terms)) {
      foreach ($terms as $term) {
        $metalTerms[] = $term->name;
        $metalSlugs[] = $term->slug;
      }
    }
  }

  if (empty($metalSlugs)) {
    $terms = get_the_terms($productId, 'product_cat');

    if (!is_wp_error($terms) && !empty($terms)) {
      foreach ($terms as $term) {
        $needle = strtolower($term->slug . ' ' . $term->name);
        if (str_contains($needle, 'gold') || str_contains($needle, 'silver')) {
          $metalTerms[] = $term->name;
          $metalSlugs[] = $term->slug;
        }
      }
    }
  }

  $stockStatus = $product->get_stock_status();
  $stockText = __('Out of stock', 'sage');
  $stockClass = 'is-out-stock';

  if ($stockStatus === 'instock') {
    $stockText = __('In stock', 'sage');
    $stockClass = 'is-in-stock';
  } elseif ($stockStatus === 'onbackorder') {
    $stockText = __('Backorder', 'sage');
    $stockClass = 'is-backorder';
  }

  $metalData = implode(',', array_unique($metalSlugs));
@endphp

<li <?php wc_product_class('captain-product-card', $product); ?> data-metal="{{ esc_attr($metalData) }}">
  <a class="captain-product-card__media" href="{{ get_permalink($productId) }}" aria-label="{{ esc_attr(sprintf(__('View %s', 'sage'), $product->get_name())) }}">
    {!! $product->get_image('woocommerce_thumbnail', ['class' => 'captain-product-card__image', 'loading' => 'lazy']) !!}
  </a>

  <div class="captain-product-card__body">
    <p class="captain-product-card__eyebrow">{{ __('Live spot-linked quote', 'sage') }}</p>

    <h2 class="captain-product-card__title">
      <a href="{{ get_permalink($productId) }}">
        {{ $product->get_name() }}
      </a>
    </h2>

    <p class="captain-product-card__price">
      {!! $priceHtml ?: wc_price((float) $product->get_price()) !!}
    </p>

    <p class="captain-product-card__stock {{ $stockClass }}">{{ $stockText }}</p>

    @if (!empty($shortDescription))
      <p class="captain-product-card__description">
        {{ wp_strip_all_tags($shortDescription) }}
      </p>
    @endif

    <div class="captain-product-card__footer">
      @if (!empty($metalTerms))
        <div class="captain-product-card__metals" aria-label="{{ __('Metal type', 'sage') }}">
          @foreach (array_slice(array_unique($metalTerms), 0, 2) as $metal)
            <span class="captain-chip">{{ $metal }}</span>
          @endforeach
        </div>
      @endif

      <a class="captain-link" href="{{ get_permalink($productId) }}">
        {{ __('Review product', 'sage') }}
      </a>
    </div>
  </div>
</li>
