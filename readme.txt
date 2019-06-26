=== Simple Website Redirect ===
Contributors: wpscholar
Donate link: https://www.paypal.me/wpscholar
Tags: site, website, redirect, redirection, forward, forwarding
Requires at least: 4.0
Tested up to: 5.2
Stable tag: 1.2.3
Requires PHP: 5.4
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

A simple plugin designed to redirect an entire website (except the WordPress admin) to another website.

== Description ==

The **Simple Website Redirect** plugin allows to you redirect an entire website (except the WordPress admin) to another website.

The URL path and query string is preserved when redirecting to the new site. Ideally, the new site would handle any one-off redirects where a URL for an old page should point to a new page. The [Redirection](https://wordpress.org/plugins/redirection/) plugin is great for this purpose.

Find out more about [website redirects for SEO](https://moz.com/learn/seo/redirection).

= Usage Instructions =

Using this plugin is simple:

1. Install the plugin
2. Activate the plugin
3. Go to 'Settings' in the WordPress admin menu and then click on 'Website Redirect'.
4. Enter the URL you want to redirect the site to, set the desired redirection type, set the status to 'Enabled' and save your changes!

Note: Redirection type can either be 'Temporary' or 'Permanent'. It is recommended that you start with 'Temporary' while testing and then convert to 'Permanent' after testing for maximum SEO benefit. Please be aware that [browsers cache permanent redirects](https://wpscholar.com/blog/browser-caching-301-redirects/).

== Installation ==

= Prerequisites =
If you don't meet the below requirements, I highly recommend you upgrade your WordPress install or move to a web host that supports a more recent version of PHP.

* Requires WordPress version 4.0 or greater
* Requires PHP version 5.4 or greater

= The Easy Way =

1. In your WordPress admin, go to 'Plugins' and then click on 'Add New'.
2. In the search box, type in 'Simple Website Redirect' and hit enter.  This plugin should be the first and likely the only result.
3. Click on the 'Install' link.
4. Once installed, click the 'Activate this plugin' link.

= The Hard Way =

1. Download the .zip file containing the plugin.
2. Upload the file into your `/wp-content/plugins/` directory and unzip
3. Find the plugin in the WordPress admin on the 'Plugins' page and click 'Activate'

== Screenshots ==

1. Just enter the URL you want to redirect the site to, set the desired redirection type, set the status to 'Enabled' and save!
2. Advanced settings let you configure exclusions to redirects on the front end. Very useful if you still need to use a front-end page builder while redirecting everything else.

== Changelog ==

= 1.2.3 =
* Bug fix to handle issues when saving without setting a redirect URL.

= 1.2.2 =
* Make sure the URL path associated with the WordPress home URL doesn't get appended to the URL when preserving URL paths.
* Properly sanitize the redirect URL to prevent redirect loops while ensuring compatibility with sites installed in a subdirectory.
* Fixed issue where an empty redirect URL could result in a non-empty redirect URL after filtering.

= 1.2.1 =
* Added the ability to bypass any redirect by appending the `?simple-website-redirect` query string.
* Ensure that redirects are prevented for admin and login pages regardless of where WordPress is installed.
* Fix minor issue where some urls would have double slashes at the beginning of the path.

= 1.2 =
* Added the ability to add exclude paths and exclude parameters which, when set, will prevent redirects from occurring if a match with one of these exclusions is found.

= 1.1 =
* Updated code to comply with strict coding standards.
* Added an option to redirect all pages to the homepage.

= 1.0.2 =
* Fix bug where redirect URL cannot contain a path
* Fix bug where redirect URL is sanitized when redirect is disabled.

= 1.0.1 =
* Fix bug where redirect occurred when on login page.

= 1.0 =
* Initial commit

== Upgrade Notice ==

= 1.0.1 =
* Fixes bug where redirect occurred when on login page.

= 1.0.2 =
* You can now provide a URL path, not just a domain for redirects.

= 1.1 =
* You now have the option to preserve URLs or redirect all pages to the homepage.

= 1.2 =
* Added the ability to configure exceptions to the site-wide redirect rules.

= 1.2.1 =
* Bug fixes to ensure proper handling of redirects across all use cases.

= 1.2.2 =
* Bug fixes to ensure proper handling of redirects for subdirectory installs under the same domain.

= 1.2.3 =
* Bug fix to handle issues when saving without setting a redirect URL.
