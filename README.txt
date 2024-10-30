=== SEO Control Center ===
Contributors: wetpaintweb
Donate link: https://www.wetpaintwebtools.com
Tags: marketing, keywords, SEO, search rank, SERP tracker, keyword ranking, rank checker, keyword position, seo tool, seo ranking, search engine tool, search engine optimization, search engine monitoring, ranking tool, rank tracker, google ranking tool, serp tracking, serp monitor, serp, seo rankings, seo plugin, seo, rankings, rank tracking, rank tracker, keyword rank, keyword tracking, google tracking, search engine tracking, google tracking, google reports
Requires at least: 3.0.1
Tested up to: 5.5.2
Stable tag: 1.3.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Track your site's keyword performance in Google, Bing, and Yahoo! Track 10 keywords daily for Free

== Description ==

The SEO Control Center brings agency-level marketing tools into WordPress, helping you grow your site's traffic and performance.

Track keyword performance in major search engines and monitor your site's progress over time.

= Why track keywords with the SEO Control Center? =

Google personalizes search results based on everything it knows about you - your search history, browsing habits, and email contents (if you use Gmail). The SEO Control Center provides you with independent and accurate search rankings for your site as the rest of the world sees it.

All of your data is stored privately & securely in the cloud and is available for download at any time.

= Getting Started =

<a href="https://www.wetpaintwebtools.com/plans/" target="_blank">Sign up for a API Key</a> to begin tracking keywords. You can track up to 10 keywords daily for free, or select a paid account to track more.

= Questions or Suggestions? =

<a href="https://www.wetpaintwebtools.com/contact-us/" target="_blank">Contact our support team</a> for help!

== Installation ==

1. Upload the `control-center` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Visit the SEO Keywords page in the left sidebar administration menu and enable the modules you'd like to use.
4. If you're using the keyword performance feature, follow the prompts to sign up for an account on www.wetpaintwebtools.com/plans

== Frequently Asked Questions ==

= Does this plugin cost anything? =

The plugin itself (hosted on WordPress.org) is freely available for download.
The keyword performance tracking feature is a paid service with subscription plans available on www.wetpaintwebtools.com

= Do you store any data from my site? =

Nope.
The only data we send to the server are keyword requests. None of your site data is sent to our servers.


== Screenshots ==

1. List of all keywords tracked for your site. Data is pulled dynamically via the API instead of stored on your site.

2. Single keyword page shows rankings over time and allows you to edit tracking parameters.

3. Bulk keyword add tool.

== Changelog ==

= 1.3.2 =
* fix link to google fonts.

= 1.3.1 =
* fix add_submenu_page call to make parameters match

= 1.3.0 =
* Refactor plugin to a more efficient structure
* Update help system
* Bug Fix
	* Fix css loading in sections of the admin besides sections for the plugin.

= 1.2.6 =
* Bug Fix
	* Fix column sorting issues

= 1.2.5 =
* Bug Fix
	* Update function calls to support PHP 5.3

= 1.2.4 =
* Bug Fix
	* Update arrays to support PHP versions older that 5.4

= 1.2.3 =
* Bug Fix
	* Keyword Requests Remaining weren't calculating correctly when there were 0 keywords in an account (new accounts).

= 1.2 =
* Data Enhancements
	* Added columns for Average Rank & Volatility (standard deviation)
	* Average Rank & Volatility are also available via CSV export
* UX Improvements
	* All columns are now sortable
	* Added the actual rank next to the change notice to make the data clearer
	* Ranking page links open in new tab on all keywords page
	* CSV Export now exports raw rankings for Yesterday, 7 Days Ago, 30 Days Ago, and 90 Days Ago
* Added upsell ads once a user reaches their keyword limit

= 1.1 =
* "Control Center" menu item is now "SEO Keywords"
	* Removed Control Center overview page
* Improved Messaging for new users on All Keywords Table
* Added graph above All Keywords Table for easy viewing of ranking status
* All Keywords Table Display Improvements
	* Make it clearer when keywords aren't ranking
	* Slight adjustments to column spacing & headers
* Single Keyword Graph Improvements
	* Only show periods we have data for
* Added intercom integration

= 1.0.3 =
* Fix Conflict with other WordPress actions

= 1.0.2 =
* User signup process improvements

= 1.0.1 =
* Fix: Keyword Performance: Fix display of Requests Remaining on Bulk Add Page
* Fix: Keyword Performance: Fix conflict with the menu location and other plugins
* Fix: Keyword Performance: Fix notices on adding bulk keywords
* Update: Keyword Performance: Updated jQuery UI DatePicker CSS to more WordPress-centric styles using John James Jacoby's work at https://github.com/stuttter/wp-datepicker-styling

= 1.0 =
* Initial plugin release.

== Upgrade Notice ==

= 1.0.3 =
* Fix Conflict with other WordPress actions

= 1.0.2 =
* User signup process improvements

= 1.0.1 =
* Fix: Keyword Performance: Fix display of Requests Remaining on Bulk Add Page
* Fix: Keyword Performance: Fix conflict with the menu location and other plugins
* Fix: Keyword Performance: Fix notices on adding bulk keywords
* Update: Keyword Performance: Updated jQuery UI DatePicker CSS to more WordPress-centric styles using John James Jacoby's work at https://github.com/stuttter/wp-datepicker-styling

= 1.0 =
* Initial plugin release.
