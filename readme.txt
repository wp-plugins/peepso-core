=== Plugin Name ===
Contributors: PeepSo, SpectrOMtech, davejesch
Donate link: http://www.peepso.com
Tags: social networking community 
Requires at least: 3.5
Tested up to: 4.2.3
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

PeepSo is the next-generation of Social Networking solution for WordPress.

== Description ==

PeepSo is an application that provides Social Networking features for your web site. PeepSo allows site users to create profiles, post to their activity stream and interact with other members. 

PeepSo core is a free plugin which possibilities can be extended by a number of supporting plugins developed and maintained by the same team that brought you PeepSo.

=FriendSo=
This plugin allows friend connections between users. Also introduces a new privacy level ‘Friends Only’ which allows to share information only with friends and not entire community.

=TagSo=
This plugin works alongside FriendSo and allows you to tag your friends in status updates. Also allows to tag people in comments, who are not necessarily friends but you’d like to get their attention.

=MsgSo=
This plugin gives users the option to send private messages between themselves. Messages can be sent to friends, non friends, you can also have a group conversation. 

=LocSo=
This is a location plugin that you can use to attach location to your posts in the community.

=PicSo= 
This plugin allows to share photos on the stream. Also adds photo wall to users’ profiles.

=VidSo=
This plugin allows to share videos from the most popular providers on the stream. It too creates a wall of videos in the users’ profiles.

=MoodSo=
This plugin gives users the option to share how they feel. It attaches the mood to a shared post. 

There are more plugins under development and those will also be released in the future and fully not only supporting but extending the possibilities of PeepSo core.

All of the plugins supporting the PeepSo core are available on http://peepso.com 


== Installation ==


Using a ZIP installation file.
Navigate to the backend area of your site.
Go to Plugins > ‘Add New’.
At the top pick ‘Upload Plugin’.
Pick the ZIP installation file of PeepSo.
Upload it.
Activate it.
You will need to confirm where to store user files.
Now the plugin is up and running with the default settings. You may change them if you wish.
Users can sign up on your website or you can add them manually in the backend.

Using the WordPress Plugin Directory in the backend of your website
Navigate to the backend area of your site.
Go to Plugins > ‘Add New’ 
Use the search field and look for ‘PeepSo’.
When it shows amongst the results click ‘Install Now’
Activate it.
You will need to confirm where to store user files.
Now the plugin is up and running with the default settings. You may change them if you wish.
Users can sign up on your website or you can add them manually in the backend.


== Frequently Asked Questions ==

= Can I run PeepSo on my host? =

Probably yes. To help with this, we have created a separate plugin that you can install and run that will test your host to see if it meets all of the requirements. You can find this plugin at: http://wordpress.org/plugins/peepso-check/

= Can I use my existing theme? =

Yes! PeepSo was designed from the beginning to be used with your existing theme and does not require a theme designed to work with PeepSo. We have tested PeepSo with several themes and Frameworks already but your theme may require some customization. You can do this by copying template files into a `peepso/` directory within your theme folder and modifying them. Please see our guide at http://www.peepso.com/customizing-peepso-for-your-theme/ for more information on customizing PeepSo.

= Does PeepSo work with WordPress MultiSite? =

Yes! We have done lots of testing to ensure that PeepSo can run on MultiSite installs.

= Where do I get support for PeepSo? =

You can follow our forums on http://www.peepso.com/community/ or use the Support Forums here on WordPress.org - http://wordpress.org/support/plugin/peepso

= Is there documentation available for PeepSo? =

Yes! You can find documentation on the use of PeepSo and designing themes and extensions for PeepSo here: http://www.peepso.com/docs/

= How do I report a bug/problem with PeepSo? =

You can report problems on the WordPress.org forums here http://wordpress.org/support/plugin/peepso or on our support site at http://www.peepso.com/support/

== Screenshots ==

1. Profile.
2. Notifications
3. Member Search
4. Edit Profile
5. Dashboard
6. Change Avatar
7. Privacy
8. Settings

== Changelog ==
= 1.0.0 =
* New Add “Register” link to “mini-profile” widget.
* New Uploaded images quality control settings.
* Impr Registration confirmation emails need to skip mail queue and be sent immediately.
* Impr Licensing improvements.
* Impr Photo attachments in messages only showing up to 5 first images, now showing all.
* Impr PeepSo Avatars outside of PeepSo.
* Impr Moods postbox presentation improvements.
* Impr Location postbox presentation improvements.
* Fix Blog posts showing under profile sub-pages.
* Fix Can’t edit caption for photos in a modal window.
* Fix Can’t edit caption for photos in a modal window.
* Fix Remove ‘view profile’ link from blocked users listing.
* Fix Logging in from PeepSo Me widget with wrong credentials doesn’t provide feedback to user.
* Fix Powered by PeepSo Showing more than once.
* Fix Likes rating setting fixed for profiles.
* Fix Bad quality of thumbnails in postbox.
* Fix PeepSo overriding themes’ styles outside of PeepSo.
* Fix A ‘cached’ version of comments, likes, edits of photos is shown in modal.
* Fix A ‘cached’ version of comments, likes, edits of videos is shown in modal.
* Fix Avatar should NOT change until “done” is clicked.
* Fix Uploaded avatars not contained by the wrapper.
* Fix Vimeo videos not playing inline on stream.

= 1.0.0-RC4 =
* Dashboard PeepSo Plugins check which are activated.
* Remove ‘Fancybox 2.0′ and use custom code.
* Videos don’t play in Firefox.
* Renamed menu items in the backend.
* Contain the notification popovers and make them scrollable within their own boundaries.
* On new install, assign proper PeepSo Roles to existing WordPress users.
* Remove the ‘drag and drop’ cursor from backend options and  the styling.
* Can’t like an individual photo from a batch upload in modal.
* Can’t report a picture from modal, when uploaded in a batch
* Can’t repost a picture from modal, when uploaded in a batch
* Sending a photo in a private message puts those photos in widgets and under profiles.
* Remove the option to add videos from PostBox in messages.
* Remove the privacy option from messages PostBox.
* Entered message doesn’t stay when switching between video and photos in messages PostBox.
* Photo attachment to messages breaks message list.
* Photo thumbnails improvements and optimisation on the Activity Stream.
* CSS improvements of the Activity Stream.
* Can’t tag people in comments in modal in photos.
* Fetching thumbs from websites returns black thumbnail.
* Compatibility of special characters in name / last name fields.
* Improved Licensing.
* Improved PeepSo Versioning.
* Optimized assets, minified JS.

= 1.0.0-RC1 =
* Initial release

== Upgrade Notice ==
* Initial release


