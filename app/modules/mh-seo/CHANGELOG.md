# Changelog

## 1.2.0

- The SEO box on the edit screen has tabs (Search, Social, Advanced, Schema), a score ring, and live length meters for the SEO title and meta description. The meters show the target range and say in words whether the text is short, good, or long.
- Search preview with a desktop and mobile view. Link previews for Facebook-style cards and for X.
- New per-post fields: social title and description, X title, description, image, and card size.
- New robots choices per post: nofollow, noarchive, nosnippet, noimageindex, and limits for snippet length, image preview size, and video preview length. The box shows the robots tag the page will send.
- Secondary keywords (up to five, notes only), a breadcrumb title used in the breadcrumb schema, and a pillar-content flag with a "Pillar" label in the SEO column.
- Schema per post: pick the article type or turn the article part off. Optional FAQ data from question headings and HowTo data from the first numbered list. Both read what is visible on the page.
- In the block editor the box and the SEO sidebar stay on the same values. Before, the box posted its own values after the sidebar's and could undo a sidebar edit.
- A post Rank Math had hidden from search stays ticked as hidden in the box until you untick it.
- `wp mh-seo migrate` also copies Yoast values, and the social, robots, canonical, breadcrumb, and pillar values from both plugins.

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
