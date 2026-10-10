{{-- Default and Custom Template pages. Named marketing templates do not include this partial. --}}
<article @php(post_class('h-entry entry-content prose'))>
  @php(the_content())
</article>
