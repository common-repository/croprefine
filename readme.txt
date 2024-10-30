=== CropRefine ===
Contributors: era404
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=FPL96ZDKPHR72
Tags: image, thumbnail, resize, media, upload, management
Requires at least: 3.2.1
Tested up to: 5.3.2
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Giving you greater control over how each of your media item sizes are cropped.

== Description ==

Extends the WordPress Media Library to allow individual control over each media item size's crop.


**For those particular about their imagery...**

WordPress's Media Library already gives authors strong, intuitive organization over their web site's images. But some users are a little more particular than others over the individual crop sizes of each media item. CropRefine is for the keen-eyed, visual types who want something fast and fastidious. 

* CropRefine provides quick-links from the Media Library to refine each item.
* A full catalog of existing crops and sizes are made available for refinement.
* If you prefer a different/better thumbnail but want to leave the other sizes untouched, CropRefine gives you that level of granularity over your imagery.
* Packaged with cropper.js, adjusting a crop is a smooth, draggable, precise refinement experience.
* Iterative backups are stored alongside each adjustment, so nothing is ever lost&mdash;only gained!
* If a re-crop just won't achieve the results you're after, a quick-upload tool is also offered for each media item size, so that you can replace that (and only that) size.
* No additional database is needed, no exhaustive setup process, no hidden license costs.

== Installation ==

1. Install CropRefine either via the WordPress.org plugin directory, or by uploading the files to your server (in the `/wp-content/plugins/` directory).
1. Activate the plugin.
1. Visit your Media Library to find the new 'Refine' option, below each media item.
1. Choose Re-Crop or Upload next to the size you wish to adjust, and the rest is handled for you.

== Screenshots ==

1. Convenient new quick-action added to Media Manager: Refine
2. Choosing the image size to re-crop or replace
3. Replace only the sizes you wish
4. A link is also provided in Image Details to CropRefine your media

== Frequently Asked Questions ==

= Are there any new features planned? =
Yes. We plan to add an iterative rollback feature to restore previous crops and replacement image sizes.

= Can i propose a feature? =
If you wish. Sure.

== Changelog ==
= 1.2.1 =
* Tested on WordPress v5.7.2;

= 1.2.0 =
* Fixed issue with locating images when WordPress adds "-scaled", by @wilhemarnoldy (thank you).
* If you experience any issues with reverse compatibility for older WP cores or themes, please share your results in the support forum.

= 1.1.0 =
* Fixed CORS issue with site url update to HTTPS, per request by @wilhemarnoldy (thank you).

= 1.0.6 =
* Adjusted styles to work better with WordPress 5.3.2

= 1.0.5 =
* Added support for repairing/restoring missing image sizes, per request by @adrienneltravis (thank you);
* Tested on WordPress v5.0.3;

= 1.0.2 =
* Updated instructions page.

= 1.0.1 =
* Additional sanitizations.

= 0.9.9 =
* Removed "beta"
* Fixed footer layout
* Fixed return to post / close window 

= 0.9.8 =
* Added Refine link to WP Image Details modal & changed minimum capability from 'manage_options' to 'edit_posts', per suggestion by joseyaz (thank you).

= 0.9.7 =
* Added Refine link to WP Media Modal view

= 0.9.6 =
* Testified/Verified Compatibility with WordPress 4.3

= 0.9.4 =
* Adjusted toolbox styles for narrower environments.

= 0.9.3 =
* Added donate link ;)

= 0.9.2 =
* Added support for registered image sizes by name.

= 0.9.1 =
* Clean/removed debugging operations to prepare for official release.

= 0.9 =
* Plugin-out only in beta, currently. Standby for official release.