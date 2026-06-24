=== Simple TOC Block ===
Contributors: matt
Tags: table of contents, toc, block, gutenberg, navigation
Requires at least: 6.4
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A lightweight Table of Contents block that auto-generates a linked outline from your post headings.

== Description ==

Simple TOC Block adds a single, focused Table of Contents block to the WordPress
block editor. Add it to any post and it automatically builds a linked outline
from your headings. The list is rendered on the server, so it appears in the
initial page HTML — good for SEO and accessibility — and it adds anchor IDs to
your headings for you.

Features:

* Auto-generates from H2/H3/H4 headings
* Choose which heading levels appear
* Numbered or bulleted list
* Accessible navigation landmark
* No configuration required to get started

== Installation ==

1. Upload the plugin to the `/wp-content/plugins/` directory, or install it
   through the Plugins screen in WordPress.
2. Activate the plugin through the 'Plugins' screen.
3. Add the "Table of Contents" block to a post that contains headings.

== Frequently Asked Questions ==

= Does it work with the classic editor? =

This is a block for the WordPress block editor (Gutenberg). It reads heading
blocks from the post content.

= Will the links scroll to my headings? =

Yes. The plugin adds matching anchor IDs to your headings automatically.

== Changelog ==

= 0.1.0 =
* Initial release: core Table of Contents block.
