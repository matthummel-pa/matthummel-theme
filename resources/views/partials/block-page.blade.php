{{-- A page whose content is blocks. Each block brings its own section and container. --}}
<div @php(post_class('mh-block-page'))>
  @php(the_content())
</div>
