# Changelog

## 1.1.1

- Category and term archives use the MH SEO term description, then the term's own description trimmed to about 155 characters. Open Graph uses the same text.
- Theme template pages prefer saved `mh_f_seo_title` and `mh_f_seo_desc` values over the theme's built-in defaults.
- Robots on noindexed pages keep `nofollow` and drop a duplicate `follow`. The max-image-preview, max-snippet, and max-video-preview directives stay.

## 1.1.0

- Add an XML sitemap at `/sitemap_index.xml`, with child sitemaps, images, and a stylesheet.
- Core sitemaps stay on until the MH SEO sitemap is enabled. `/sitemap.xml` and `/wp-sitemap.xml` then redirect to the new index.
- Leave a page out of the sitemap on its own, and keep noindexed, password-protected, and unpublished pages out.
- Offer updates from the private Origin repository when `MH_SEO_UPDATE_TOKEN` is set on the site. The token is not part of the plugin.

## 1.0.1

- Remove the theme title and description callbacks before the head is printed.
- Use `og:type` website on the front page, the posts page, and other non-singular views.

## 1.0.0

- First release.
