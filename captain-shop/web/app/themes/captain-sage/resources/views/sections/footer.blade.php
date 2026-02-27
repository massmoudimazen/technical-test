<footer class="content-info">
  <div class="content-info__inner">
    <div class="content-info__meta">
      <p>
        &copy; {{ date_i18n('Y') }} {{ get_bloginfo('name') }}.
        {{ __('All rights reserved.', 'sage') }}
      </p>
    </div>

    <div class="content-info__widgets">
      @if (is_active_sidebar('sidebar-footer'))
        @php(dynamic_sidebar('sidebar-footer'))
      @endif
    </div>
  </div>
</footer>
