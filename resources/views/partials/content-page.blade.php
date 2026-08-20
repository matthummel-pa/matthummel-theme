{{-- Pages are custom-field layouts. Gutenberg post_content is not rendered. --}}
<article @php(post_class('page-fields-only'))>
  <p class="lead">{{ __('This page needs a named theme template (Home, About, Work, Services, Code, Contact, Now, or Writing). Gutenberg and patterns are off.', 'sage') }}</p>
</article>
