=== TOCflow — Table of Contents Block ===
Contributors: matthummel
Tags: table of contents, toc, gutenberg, navigation, accessibility
Requires at least: 6.4
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A lightweight, server-rendered Table of Contents block that auto-builds a linked outline from your post headings.

== Description ==

TOCflow adds a single, focused **Table of Contents** block to the WordPress
block editor. Add it to any post and it automatically builds a linked outline
from your headings. The list is rendered on the server, so it appears in the
initial page HTML — good for SEO and accessibility — and it adds anchor IDs to
your headings for you.

**Features**

* Auto-generates from H2 / H3 / H4 headings
* Choose which heading levels appear
* Numbered or bulleted list
* Accessible `<nav>` navigation landmark
* Color, spacing, and typography controls via standard block settings
* No configuration required to get started

Server-rendered means there is no heavy JavaScript on the front end and the
outline is present the moment the page loads.

Support and documentation: https://matthummel-pa.github.io/tocflow/
Source code: https://github.com/matthummel-pa/tocflow

== Installation ==

1. Upload the plugin to the `/wp-content/plugins/` directory, or install it
   through the Plugins screen in WordPress (Plugins → Add New → Upload Plugin).
2. Activate the plugin through the **Plugins** screen.
3. Edit a post that contains Heading blocks, click the **+** inserter, search
   for **"Table of Contents,"** and add the block.
4. (Optional) Select the block and open the **Settings** sidebar to set the
   title, choose heading levels, or switch to a numbered list.

== Frequently Asked Questions ==

= Does it work with the Classic Editor? =

No. TOCflow is a block for the WordPress block editor (Gutenberg). It reads
Heading blocks from the post content.

= Will the links scroll to my headings? =

Yes. The plugin adds matching anchor IDs to your headings automatically. If a
heading already has a custom HTML anchor, that one is respected.

= Will it slow down my site? =

No. The outline is rendered on the server as plain HTML — there is no heavy
front-end JavaScript.

= Can I have more than one TOC on a page? =

It is designed for one per post. Multiple blocks will each list the same
headings.

= The block is empty — why? =

Make sure the post has real Heading blocks (not bold text), and that the heading
levels you used are enabled in the block settings.

== Screenshots ==

1. The block settings panel — heading text, which levels to include (H2/H3/H4),
   and numbered vs. bulleted list.
2. The front-end output — a nested, linked Table of Contents generated
   automatically from the post's headings.

== Changelog ==

= 0.1.0 =
* Initial release: core Table of Contents block with selectable heading levels,
  numbered/bulleted lists, automatic anchor IDs, and an accessible nav landmark.

== Upgrade Notice ==

= 0.1.0 =
First public release of TOCflow.
