=== Matt Hummel Newsletter ===
Contributors: matthummel
Tags: newsletter, email
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 8.3
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Self-hosted newsletter. Subscribers and issues stay in WordPress. Mail goes out through wp_mail.

== Description ==

A personal newsletter for matthummel.com. Addresses, issues, and the send log live in this WordPress database. Nothing is sent to Mailchimp, HubSpot, or another marketing service.

* Double opt-in for new signups.
* Unsubscribe and preferences links that work without an account.
* One-click unsubscribe header on each issue.
* Create a newsletter in five steps: template, content, subject, preview, then send or schedule.
* Layouts: Standard, Welcome, Plain, and Feature. Standard reuses an intro and a sign-off. Welcome is a short letter with its own subject and body. Plain leaves out the featured image. Feature puts that image on top.
* Templates: blog update, blog digest, and a custom message. Each one has a note from you. Reusable copy is edited under Get updates → Settings.
* Draft an issue in the block editor, preview it, and send a test to yourself.
* Publishing a post saves a blog update draft with a blank note. Automatic sending stays off until you turn it on.
* Import a CSV of addresses you already have permission to email.
* Keep a private copy of each finished send under Get updates → Sent archive.

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

= Where is a sent issue kept? =

Under Get updates → Sent archive. That copy stays as it was sent. A sent issue is read-only. Duplicate it when you want a new draft. There is no public archive page. A public web archive is not built. If it is added later, it stays off until you turn it on.

== Changelog ==

= 1.1.0 =
* Choose Standard, Welcome, Plain, or Feature when you create a letter. The dashboard shows a preview. Intro, sign-off, and the welcome letter are edited under Settings → Reusable copy. Nothing sends until you send it.
* The wizard preview is an email frame. Desktop and Mobile change the width. Edits update the frame without reloading the page. Preview links do not leave the page.

= 1.0.1 =
* Get updates admin screens use navy and white cards. Sending, tracking, and automatic send are unchanged.

= 1.0.0 =
* Sign up, confirm by email, and unsubscribe without an account. The confirmation link opens a page. The address is added only after the button is pressed. First name and last name are optional on that page.
* Create a newsletter one step at a time, or edit the same draft in the block editor.
* Choose a blog update, a blog digest, or a custom message. Each template starts with “Hi {first_name|there},”.
* A blog update includes the post’s featured image, linked to the post. A digest uses a thumbnail. You can hide or replace that image for one send.
* Publishing a post saves a blog update draft. Automatic sending stays off until you turn it on.
* Issues use one heading, real link text, alt text, and a plain-text part. Sending is blocked when an image has no alt text.
* Click and open links cannot unsubscribe. Confirmation email for the same address is limited.
* Import and export a CSV of addresses you already have permission to email.
* Sent archive keeps a private copy of each finished send. A sent issue is read-only. Duplicate it to start a new draft. Archive hides an old issue from the main list and keeps the copy.
* Get updates shows audience counts, one Create newsletter button, and recent issues with status, sent time, and real delivered or failed counts. Open and click rates are not shown.
