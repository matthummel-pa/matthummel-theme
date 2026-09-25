# How to send a newsletter

This is the letter that goes out from the site. Addresses stay here. Mail goes out through WordPress, not a separate newsletter service.

Open **Get updates** in wp-admin, then **Create newsletter**. The same button is on the Get updates page when you are logged in as an administrator.

The draft saves as you go. You can close the tab and come back. On the dashboard, **Continue** opens the step you left.

## The five steps

1. **Template.** Pick one:
   - **Blog update** is one post. It includes the image, title, excerpt, and a Read more button.
   - **Blog digest** is two to six posts, each in its own card.
   - **Custom message** is a letter or announcement. You can add an image and a button.
2. **Content.** Write your note. Bold, italic, links, and lists are enough. Blog templates put that note above the posts, and you can add a P.S. under them. The latest post is already selected for a blog update. A digest starts with the latest posts. Search if you want older ones.
3. **Subject.** The subject and the preview text start from what you wrote. Aim for about 50 characters in the subject and about 90 in the preview text. The preview text is the line under the subject in the inbox.
4. **Preview.** Look at the desktop width and the phone width. Send a test to your own email before you send it to anyone else.
5. **Send.** The page shows how many subscribed addresses will get it. Nothing goes out until you check the box and choose **Send now** or **Schedule**.

**Open the block editor** is on every step after the draft exists. That editor and this wizard are the same draft. Use the wizard for the normal path. Use the editor when you want to move blocks around yourself.

## When you publish a post

Publishing a post saves a **Blog update** draft. The note and the P.S. are empty, ready for you to write. It does not email anyone. Open it with **Continue**, add your note, preview it, and send it yourself.

Automatic sending stays off unless you turn it on under **Get updates → Settings**. Leave it off until the From address is a mailbox on your domain and SPF, DKIM, and DMARC are in place.

## Writing an accessible newsletter

The template already sets the type size, the colors, and the footer. You still choose the words.

- Write a subject and a preview line. Keep the subject near 50 characters. Over 60, the wizard warns you, because inboxes cut it off.
- Describe every image in the alt text field. If the picture is only decoration, leave it out. The email should still make sense with images turned off.
- Name the link. Use “Read more: ” plus the post title, or a phrase that says where the link goes. “Click here” and a bare “Read more” are flagged before you send.
- The button is real text, not a picture of text. Do not paste a screenshot of a sentence in place of the note.
- Send a test and look at it on your phone. The layout stacks into one column.
- Every issue includes your mailing address, a line about why the person got it, and an unsubscribe link. One-click unsubscribe is on the message as well. Tracking stays off unless you turn it on in settings. When it is on, click and open links are separate from the unsubscribe link.

New signups confirm by email before they are on the list. The link in that email opens a confirm page. The address is added only when they press the button. A scanner that only opens the link does not confirm them. The From address should be a mailbox on this site’s domain.
