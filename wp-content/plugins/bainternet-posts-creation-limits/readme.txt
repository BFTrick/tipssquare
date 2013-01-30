=== Bainternet Posts Creation Limits ===
Contributors: bainternet,adsbycb 
Donate link: http://en.bainternet.info/donations
Tags: limits, cpt limits, posts per user, limit pages, limit user, post creating limit, post limit, user post limit
Requires at least: 3.0
Tested up to: 3.5 
Stable tag: 2.9

this plugin helps you to limit the number of posts/pages/custom post types each user can create on your site.

== Description ==

this plugin helps you to limit the number of posts/pages/custom post types each user can create on your site. say you have a multiple author blog and you want to limit the number of posts each author can post.

very simple and light wieght plugin that runs only when user tries to crate a new post of any kind (post,page,attachment,or any custom post type) and check if he has reached his limit.


**Main Feature:**

*	Limit number of any post type creation.
*	Select Post Status to count. (NEW)
*	Limit number of any post type creation by user Role. (NEW)
*	Limit number of any post type creation by user ID. (NEW)
*	New Limit Rule System (faster and stable).
*	Custom blocked message For each Rule. (NEW)
*	MultiSite Support. (Fixed and works better then before)
*	ADD NEW Links are removed when limit is reached (NEW)
*	0 Now means ZERO so Its actually blocks the user from creating at all.
*	Shortcode to limit front end post creation (NEW).

!! Do Not Try with admin user beacuse he is never limited unless you are on a multisite install and then the super admin is never limited.


any Feedback is Welcome.

check out our [other plugins][1]

 [1]: http://en.bainternet.info/category/plugins


== Installation ==

Simple steps:

1. Extract the zip file and just drop the contents in the wp-content/plugins/ directory of your WordPress installation.

2. Then activate the Plugin from Plugins page.

3. Go to Plugins option panel named "Post Creation Limits" under options.

4. Setup your limits Per each post type.

5. save!.

Using in Multisite Installation:

1. Extract the zip file and just drop the contents in the wp-content/mu-plugins/ directory of your WordPress installation. (This is not created by default. You must create it in the wp-content folder.)

2. Go to Plugins option panel named "Post Creation Limits" under options.

3. Setup your limits Per each post type.

4. save!.

== Frequently Asked Questions ==

= I have Found a Bug, Now what? =

Simply use the <a href=\"http://wordpress.org/tags/bainternet-posts-creation-limits?forum_id=10\">Support Forum</a> and thanks a head for doing that.


= It's Not Working...? =

 Make sure you are not loged in as admin user or user with role that can manage options or on a multisite installation make sure you are not the super admin or a user with role that can manage network.

= How To Use in MultiSite =

 Extract the zip file and just drop the contents in the wp-content/mu-plugins/ directory of your WordPress installation. (This is not created by default. You must create it in the wp-content folder.) The “mu” does not stand for multi-user like it did for WPMU, it stands for “must-use” as any code placed in that folder will run without needing to be activated. 
 After you define you settings in the main site it will apply to all sub sites, you can also have a site specific settings by entering that sites dashboard >> post creation limits panel and save the site specific rules.
 
= How To Use On FrontEnd Forms? =

 Just for that you can use the built in Shortcode. simply wrap your form with [IN_LIMIT] tags ex:
`[IN_LIMIT] form code here ... [/IN_LIMIT]`

 You can even use nested shortcodes ex:
`[IN_LIMIT] [form shortcode here] [/IN_LIMIT]`

= What are the Parameters of the Shortcode? =

`'message' => to overwrite rule message,
'm' => when a user is not logged in,
'use_m' => wheter to overwrite or not,
'type' => the post type that needs to be checked.`

ex:
`[IN_LIMIT use_m="true" message="no more pages for you", m="only looged in users can post here" type="page"] [form shortcode here] [/IN_LIMIT]`

= Any Filter Hooks? =
 
 Yes, many :)

`'bapl_shortcode_not_logged_in' -> log in message`
`'bapl_shortcode_network_admin' -> network admin on multisite`
`'bapl_shortcode_admin' -> admin on none multisite`
`'bapl_shortcode_limited' -> when a user is limited message`
`'bapl_shortcode_ok' -> when a user is ok to see the form`
`'post_creation_limits_limited_message_class' -> error class on backend when limited``



that's it so far.
== Screenshots ==
1. Simple admin panel LIMITS and rules

2. add/edit limit rule panel

3. user blocked for reaching his limits when trying to create a new post.

== Changelog ==
2.9 Added Filters `bapl_Count_filter` for count to allow you to filter based on your own rules (other then what is set in the plugin).
Fixed role change on rule edit.

2.8 Fixed post type "any" limitation bug.

2.7 Fixed Missing argument 2 for bapl::limit_xml_rpc() issue.

2.6 `trash` is now  check in `any` status as well.

2.5 Added time span.

2.4 Added Any to post type rules.

2.3 Fixed HTML message issues.
Added `bapl_limited_message_Filter` filter hook for message.

2.2.2 Globalized class var.
added action hook for custom check.

2.2.1 Added casting to avoid errors on foreach calls

2.2 added filter shortcode for front end forms
added filter hooks.

2.1 Fixed Multisite support.
Added site specific (in a multisite) settings option.
custom limit block message.
select post status to count.


2.0 Hide add new whe limit is reached.
auto migration from old limits to new rule system.
0(Zero) no longer means unlimited so its actually a sure block.

1.9 Admin UI remake.
New Rule System.

1.8 added block by user id.

1.7 Re Coded in OOP.

1.6 none public update

1.5 none public update

1.4 none public update

1.3 none public update

1.2 none public update

1.1 quick bug fix

1.0 Major update, added custom blocked message feature.
new limit by role feature.
added multisite support.

0.6 Changed post Count function to speed it up.

0.5 added custom post type support and recoded most of it.

0.4 added options panel.

0.3 quick bug fix.

0.2 admin is now never limited.

0.1 inital release.