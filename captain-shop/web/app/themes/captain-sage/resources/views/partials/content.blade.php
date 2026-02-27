<article @php(post_class('entry-card'))>
  <header class="entry-card__header">
    <h2 class="entry-title">
      <a class="entry-title__link" href="{{ get_permalink() }}">
        {!! $title !!}
      </a>
    </h2>

    @include('partials.entry-meta')
  </header>

  <div class="entry-summary">
    @php(the_excerpt())
  </div>
</article>
