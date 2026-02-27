<form role="search" method="get" class="search-form" action="{{ home_url('/') }}">
  <label class="search-form__label">
    <span class="sr-only">
      {{ _x('Search for:', 'label', 'sage') }}
    </span>

    <input
      class="search-form__input"
      type="search"
      placeholder="{!! esc_attr_x('Search &hellip;', 'placeholder', 'sage') !!}"
      value="{{ get_search_query() }}"
      name="s"
      aria-label="{{ esc_attr_x('Search for:', 'label', 'sage') }}"
    >
  </label>

  <button class="search-form__button">{{ _x('Search', 'submit button', 'sage') }}</button>
</form>
