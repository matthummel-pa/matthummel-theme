# How to send a newsletter

This is the letter that goes out from the site. Addresses stay here. Mail goes out through WordPress, not a separate newsletter service.

Open **Get updates** in wp-admin. That screen is the audience home: subscribed, pending, and unsubscribed counts, then recent newsletters with status, sent time, and delivered or failed counts after a send starts. **Create newsletter** is the main button. Subscribers, Import, Sent archive, and Settings are links on that screen and in the menu. The same create button is on the public Get updates page when you are logged in as an administrator. The dashboard does not show open or click rates.

The draft saves as you go. You can close the tab and come back. On the dashboard, **Continue** opens the step you left. The wizard labels the current step in words, not only by color.

## The five steps

1. **Template.** Pick one:
   - **Blog update** is one post. It includes the featured image, title, excerpt, and a Read more button. The image links to the post. If the post has no featured image, the email skips that block.
   - **Blog digest** is two to six posts, each in its own card, with a thumbnail when the post has a featured image.
   - **Custom message** is a letter or announcement. You can add an image and a button.
2. **Content.** Write your note. It starts with `Hi {first_name|there},`. Bold, italic, links, and lists are enough. You can also use `{last_name}` and `{full_name}`. The preview fills those with sample names. Blog templates put that note above the posts, and you can add a P.S. under them. The latest post is already selected for a blog update. A digest starts with the latest posts. Search if you want older ones. Uncheck **Show the featured image** to leave it out of this send. On a blog update you can replace it with another image from the library. Alt text comes from the image, then the post title.
3. **Subject.** The subject and the preview text start from what you wrote. Aim for about 50 characters in the subject and about 90 in the preview text. The preview text is the line under the subject in the inbox.
4. **Preview.** Look at the desktop width and the phone width. Send a test to your own email before you send it to anyone else.
5. **Send.** The page shows how many subscribed addresses will get it. Nothing goes out until you check the box and choose **Send now** or **Schedule**.

**Open the block editor** is on every step after the draft exists. That editor and this wizard are the same draft. Use the wizard for the normal path. Use the editor when you want to move blocks around yourself.

The first step also picks a layout: **Standard**, **Welcome**, **Plain**, **Feature**, or **Blog post**. Standard is the usual letter. Its intro and sign-off are edited once under **Get updates → Settings → Reusable copy**, then reused. Plain is that letter with no featured image. Feature and Blog post put the image full width under the masthead, then the title. Welcome is a short letter for a new subscriber, with its own subject and body in that same card. Picking Welcome does not email the list. **Letter style** on that settings screen is the default shell: Card, Banner, or Paper. Masthead can sit left or center. The button can be solid or outline. The wizard has the same controls, and the preview updates when you change them. A saved letter keeps its own look. The dashboard shows the current letter in a small email preview. In the wizard, **Preview** is an email frame: from, subject, and the intro as the preheader. Desktop is about 600 pixels wide and Mobile is about 375. It updates as you change the layout, subject, note, or letter style. It does not send mail. **Advanced** is the other editor on that same screen. It lists intro, heading, body, an optional button, and sign-off. Empty intro and sign-off keep the reusable lines. Simple stays the usual path.

## When you publish a post

Publishing a post saves a **Blog update** draft. The note starts with `Hi {first_name|there},` and the P.S. is empty. The featured image is included when the post has one. It does not email anyone. Open it with **Continue**, edit the note, preview it, and send it yourself. If you later turn on automatic sending, that same draft is what goes out, image included.

Automatic sending stays off unless you turn it on under **Get updates → Settings**. Leave it off until the From address is a mailbox on your domain and SPF, DKIM, and DMARC are in place.

## Writing an accessible newsletter

The template already sets the type size, the colors, and the footer. You still choose the words.

- Write a subject and a preview line. Keep the subject near 50 characters. Over 60, the wizard warns you, because inboxes cut it off.
- Describe every image in the alt text field. If the picture is only decoration, leave it out. The email should still make sense with images turned off.
- Name the link. Use “Read more: ” plus the post title, or a phrase that says where the link goes. “Click here” and a bare “Read more” are flagged before you send.
- The button is real text, not a picture of text. Do not paste a screenshot of a sentence in place of the note.
- Send a test and look at it on your phone. The layout stacks into one column.
- Every issue includes your mailing address, a line about why the person got it, and an unsubscribe link. One-click unsubscribe is on the message as well. Tracking stays off unless you turn it on in settings. When it is on, click and open links are separate from the unsubscribe link.

New signups confirm by email before they are on the list. The link in that email opens a confirm page with optional first name and last name fields. The address is added only when they press the button. A missing name does not block that. A scanner that only opens the link does not confirm them. They can change the name later on the preferences page. The From address should be a mailbox on this site’s domain.

## Sent archive

When a send finishes, WordPress keeps a private copy under **Get updates → Sent archive**. That copy is the letter as sent: subject, preview text, From name and address, template, start and finish times, who sent it, and the recipient, delivered, and failed counts. The list name is Subscribed, or Allowlist when a send is limited to a test list. Merge tags stay as placeholders. Subscriber addresses are not in the copy.

The list can be sorted by date, subject, or template. You can search, filter by template and date, and page through it. Open a row to read the saved letter. Download one issue as HTML or EML, or download a CSV of the archive details.

A sent issue is read-only. Use **Duplicate as new draft** when you want to send a revision. **Archive** hides an old issue from the issues list and from the default archive list. The copy stays. Choose **Archived** in the filter to see it again. Nothing in the archive is deleted on its own. **Delete** asks you to confirm, and only an administrator can do it.

There is no public archive page.

## Later

A public web archive, a page of sent issues that anyone could open, is not built. If it is added later, that setting stays off until you turn it on.
