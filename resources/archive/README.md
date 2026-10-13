# Archive (to delete)

Field-based page templates that were replaced by block pages (3.7.x). They sit
outside `resources/views/`, so WordPress and Acorn do not load them, and the
packed theme zip leaves this folder out.

Each page's old copy is still in its `mh_f_*` post meta. To put a page back:
move its template to `resources/views/`, set the page's template to it, and
delete the page's `_mh_block_page` and `_mh_block_page_from` meta.

Delete this folder once the block pages have been live long enough that nobody
needs the old markup for reference.
