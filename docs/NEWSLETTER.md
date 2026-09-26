# How to send a newsletter

This is the letter that goes out from the site. Addresses stay here. Mail goes out through WordPress, not a separate newsletter service.

Open **Get updates** in wp-admin. That screen is the audience home: subscribed, pending, and unsubscribed counts, then recent newsletters with status, sent time, and delivered or failed counts after a send starts. **Create newsletter** is the main button. Subscribers, Import, Sent archive, and Settings are links on that screen and in the menu. The same create button is on the public Get updates page when you are logged in as an administrator. The dashboard does not show open or click rates.

The draft saves as you go. You can close the tab and come back. On the dashboard, **Continue** opens the step you left. The wizard labels the current step in words, not only by color.

## The five steps

1. **Template.** Pick one:
   - **Blog update** is one post. It includes the featured image, title, excerpt, and a Read more button. The image links to the post. If the post has no featured image, the email skips that block.
   - **Blog digest** is two to six posts, each in its own card, with a thumbnail when the post has a featured image.
   - **Custom message** is a letter or announcement. You can add an image and a button.
2. **Content.** Write your note. It starts with `Hi {first_name|there},`. Bold, italic, links, and lists are enough. You can also use `{last_name}` and `{full_name}`. The preview fills those with sample names. Blog templates put that note above the posts, and you can add a P.S. under them. The latest post is already selected for a blog update. A digest starts with the latest posts. Search if you want older ones. Uncheck **Show the featured image** to leave it out of this send. On a blog update you can replace it with another image from the library. Alt text comes from the image, then the post title. Every template also has **Add image**, which opens the media library. A welcome letter shows a default photo until you choose one.
3. **Subject.** The subject and the preview text start from what you wrote. Aim for about 50 characters in the subject and about 90 in the preview text. The preview text is the line under the subject in the inbox.
4. **Preview.** Look at the desktop width and the phone width. Send a test to your own email before you send it to anyone else.
5. **Send.** The page shows how many subscribed addresses will get it. Nothing goes out until you check the box and choose **Send now** or **Schedule**.

**Open the block editor** is on every step after the draft exists. That editor and this wizard are the same draft. Use the wizard for the normal path. Use the editor when you want to move blocks around yourself.

The first step also picks a layout: **Standard**, **Welcome**, **Plain**, **Feature**, or **Blog post**. They stay light. The page behind the letter is light grey, with more of that grey around the card. The words sit in one white block, with padding on the top, right, bottom, and left. Standard is a muted intro, a large title, then the letter, with the image under the title when the issue has one. Welcome is shorter and centered, with a default photo at the top and no navy panel. Plain keeps a navy rule beside the intro and a text link instead of a button. Feature insets the image on the white card, then the title. Blog post is an announcement: inset image, a category chip, title, excerpt, the headings under **In this note**, then **Read the post**. On a Blog post letter, **Sections** can leave out the image, excerpt, headings, categories, or button. Picking Welcome does not email the list. **Letter style** on that settings screen is the default shell: Card, Banner, or Paper. Masthead can sit left or center. Welcome stays centered either way. The button can be solid or outline. **Font** is Sans, Serif, Humanist, or Editorial. Editorial uses a serif title and a sans body. **Size** is Regular (16px) or Roomy (18px). **Variant** is Focused, Detailed, or Digest. New letters start on Detailed. Focused is one idea and one action. Detailed Standard turns the intro into a dek. Detailed Welcome adds the welcome list. Detailed Plain adds the date. Detailed Feature uses the intro as a caption under the image. Detailed Blog post adds the date, a reading time, and **Why I wrote this**. Digest puts a why-it-matters line first, then a short list, then one link. Digest Welcome puts the list before the letter. The wizard has the same style, font, size, and variant controls, and the preview updates when you change them. A saved letter keeps its own look. **Header and social** takes a header image from the library or a URL, plus Site, GitHub, LinkedIn, Bluesky, YouTube, and Instagram. Each link is an icon and the network name. Site, GitHub, LinkedIn, and Bluesky start filled. Clear a field to leave it off the letter. The icons can sit in the footer or at the end of the white letter. The postal address and the unsubscribe link stay in the footer. Unsubscribe and Manage preferences are their own pages. Opening unsubscribe asks for a confirm click. It does not unsubscribe from the link alone. **Newsletter rules** can skip a post that was already sent and limit the Blog post picker to chosen categories. The dashboard shows the current letter in a small email preview. In the wizard, **Preview** is an email frame: from, subject, and the preview text. That line starts as the reusable intro. **HTML email** is the styled letter. **Plain text** is the same letter with the styling removed, which is also the plain part of the message people receive. Desktop is about 600 pixels wide and Mobile is about 375. It updates as you change the layout, subject, note, or letter style. It does not send mail. **Advanced** is the other editor on that same screen. It lists intro, heading, body, an optional button, and sign-off. Empty intro and sign-off keep the reusable lines. Simple stays the usual path.

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
