{{--
  Catalog sort select. id matches the visually-hidden label in archive-product.

  @see https://woocommerce.com/document/template-structure/
  @version 9.7.0
--}}
@php
  $catalog_orderby_options = is_array($catalog_orderby_options ?? null) ? $catalog_orderby_options : [];
  $orderby = (string) ($orderby ?? '');
@endphp
<form class="woocommerce-ordering" method="get">
  <select
    name="orderby"
    id="mh-catalog-orderby"
    class="orderby"
    aria-label="{{ __('Sort products', 'sage') }}"
  >
    @foreach ($catalog_orderby_options as $id => $name)
      <option value="{{ $id }}" @if ($orderby === (string) $id) selected @endif>{{ $name }}</option>
    @endforeach
  </select>
  <input type="hidden" name="paged" value="1" />
  {!! wc_query_string_form_fields(null, ['orderby', 'submit', 'paged', 'product-page'], '', true) !!}
</form>
