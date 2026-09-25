=== Matt Hummel Newsletter ===
Contributors: matthummel
Tags: newsletter, email
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 8.3
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Self-hosted newsletter. Subscribers and issues stay in WordPress. Mail goes out through wp_mail.

== Description ==

A personal newsletter for matthummel.com. Addresses, issues, and the send log live in this WordPress database. Nothing is sent to Mailchimp, HubSpot, or another marketing service.

* Double opt-in for new signups.
* Unsubscribe and preferences links that work without an account.
* One-click unsubscribe header on each issue.
* Create a newsletter in five steps: template, content, subject, preview, then send or schedule.
* Templates: blog update, blog digest, and a custom message. Each one has a note from you.
* Draft an issue in the block editor, preview it, and send a test to yourself.
* Publishing a post saves a blog update draft with a blank note. Automatic sending stays off until you turn it on.
* Import a CSV of addresses you already have permission to email.

Addresses copied from the original footer list are marked as legacy single opt-in. They stay subscribed and are not asked to confirm again.

== Installation ==

1. Run `bash .github/scripts/pack-plugin.sh` from the theme repo, or download the plugin zip from the workflow artifact.
2. In wp-admin, go to Plugins → Add New → Upload Plugin and install `matthummel-newsletter.zip`.
3. Activate the plugin. It adds Get updates and Email preferences if those pages are missing.
4. Set the From address and mailing address under Get updates → Settings.
5. Publish SPF, DKIM, and DMARC for the From domain before a real send. Install an SMTP plugin if the host does not deliver `wp_mail` on its own.

== Frequently Asked Questions ==

= Where do addresses go? =

They stay in this WordPress database. The plugin does not call a third-party newsletter service.

= What happens to the old footer list? =

Those addresses are copied in as subscribed. They signed up before double opt-in, so they are not sent another confirmation.

= Does publishing a post email everyone? =

No. It saves a draft issue for review. Automatic sending is a setting, and it is off.

== Changelog ==

= 1.0.0 =
* Sign up, confirm by email, and unsubscribe without an account.
* Create a newsletter one step at a time, or edit the same draft in the block editor.
* Choose a blog update, a blog digest, or a custom message. Each template has a note from you.
* Publishing a post saves a blog update draft with a blank note. Automatic sending stays off until you turn it on.
* Issues use one heading, real link text, alt text, and a plain-text part. Sending is blocked when an image has no alt text.
* Click and open links cannot unsubscribe. Confirmation email for the same address is limited.
* Import and export a CSV of addresses you already have permission to email.
