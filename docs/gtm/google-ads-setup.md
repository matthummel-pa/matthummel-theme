# Google Ads setup (Cobble & Candle landing page)

Container: `GTM-5H3KSG9X`. Do all of this in Tag Manager. Never add `gtag.js` or an Ads snippet to the theme.

## Status: paused (placeholder)
The theme switch `mh_google_ads_enabled` is off, so ad consent stays denied. Return `true` from that filter (for example in a small must-use plugin) when the steps below are done.

## Before ads run
1. Privacy policy page: say the site uses Google Ads cookies to measure which ads lead to a purchase. Edit it in the block editor.
2. Indexing: the theme forces `index, follow` on this template. Also clear Robots Meta on page 1757 in Rank Math so the sitemap lists it.
3. Link Google Ads to the GA4 property (Admin > Product links) and turn on auto-tagging in Ads.

## Tags to create
| Tag | Type | Trigger | Notes |
| --- | --- | --- | --- |
| Google tag | Google tag, ID `AW-XXXXXXXXX` | All Pages | Consent checks: built-in `ad_storage`. |
| Ads purchase | Google Ads Conversion Tracking | Custom event `purchase` | Conversion ID and label from Ads. Value `{{DLV ecommerce.value}}`, currency `{{DLV ecommerce.currency}}`, transaction ID `{{DLV ecommerce.transaction_id}}`. |
| Ads buy click (optional) | Google Ads Conversion Tracking | Custom event `cc_buy_click` | Secondary conversion. Do not use it for bidding. |
| Ads remarketing (optional) | Google Ads Remarketing | All Pages | Needs `ad_personalization`. |

Data layer variables: `ecommerce.value`, `ecommerce.currency`, `ecommerce.transaction_id`.

## Test before publishing
- GTM Preview on the landing page: confirm the Ads tags show consent `granted` for a US visitor and are blocked for a visitor in the EEA until they click "Allow cookies".
- Complete a Freemius sandbox purchase and confirm `purchase` fires once with a value and transaction ID.
- Google Ads > Conversions: status becomes "Recording conversions" after the first real purchase.
