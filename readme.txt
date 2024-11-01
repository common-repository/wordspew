=== WordSpew ===

Tags: Shoutbox, AJAX, Jalenack, WordSpew, Chat
Contributors: jalenack

Author: Andrew Sutherland
http://blog.jalenack.com
Originally released May 2, 2005

This release: September 1, 2005
Version: 1.16

Based on an original idea by Alexander Kohlhofer:
XHTML live Chat
http://www.plasticshore.com/projects/chat/

Uses Adam Michela's Fade Anything Technique
http://www.axentric.com/posts/default/7

Released under Creative Commons license:
http://creativecommons.org/licenses/by-nc-sa/2.0/

--------------------------------------------------

== Description ==

This plugin will create a shoutbox on your wordpress blog. But it isn't any old shoutbox. It uses AJAX, a technology that allows  information to be transmitted to the server without the user refreshing the page. So what makes this special is that your users can carry out live chats from your blog without having to refresh the page. It's kind of like instant messaging!

Features: It distinguishes whether a user is logged in to wordpress, and if he/she is, then it users their nickname as their name, so that they don't have to type it in every time they refresh the page. Also, if that user is an admin, they get a special link "Administrate" This will take them to the admin panel for the plugin. This admin panel allows you to set colors, change timing, and edit/delete comments.

This plugin works with AJAX in IE6/Windows, Firefox, Safari, Konqueror, and Opera 8.01. It will still work with any other browser, just sans-AJAX.

== Installation ==

WordSpew will only work in WordPress blogs version 1.5 or higher
   
1. Put the folder labeled "wordspew" in your plugins folder. This is located at /wp-content/plugins/.

2. Browse to your plugins manager in the admin section of your blog. Activate Jalenack's Wordspew plugin. This will automatically create a table in your database that the plugin will use for its content.
   
3. Now you have to choose where you want the shoutbox to show on your blog. I suggest putting it in your sidebar. To edit the sidebar, go to /wp-content/themes/*yourtheme*/sidebar.php . Then place this snippet:
   
   <?php jal_get_shoutbox(); ?>
   
in the template. This php function will print the shoutbox. Generally, follow the convention that other sidebar items use. Never put the function within its own <ul> tags. Most wordpress sidebars use unordered lists (<ul>) to organize their content. You probably want to put it in its own list item. So to do this, you would insert
   
   <li><h2>Live Shoutbox</h2>
	<?php jal_get_shoutbox(); ?>
   </li>
   
After the </li> where you want it to go. Just to make sure, try validating your html and make sure you got it right. If you can't figure it out, you know who to call!

And now, enjoy!

4. You can edit the look and functionality of your shoutbox by browsing to your admin panel and clicking on Manage. Under there, there should be a submenu titled "Live Shoutbox". Go there. By default, only users with Level 8 or higher status can access it. Site Admins are level 10, so you have nothing to worry about.

== Upgrading == 

For those with greater than that, just replacing the old files with the new ones will do.

People who used older versions of Wordspew (less than version 1.0 FINAL) will need to follow these instructions to get the plugin up to date.

1. DEACTIVATE Wordspew in your plugins panel. 

2. Upload and replace all three files in the wordspew folder (wordspew.php, css.php, and fatAjax.php). If you have customized your css file, you may not want to replace it.

3. ACTIVATE Wordspew in your plugins panel. This is key, because the plugin relies on activation to create tables in the database.

4. You're done! You can now administer the plugin through Admin Panel > Manage > Live Shoutbox.
   
== Frequently Asked Questions ==

= How do I change the style of the shoutbox?
  
The Administration panel allows you to edit some colors, but if that's not enough, you can edit the css file directly. If you'd like to edit it, just edit the css.php file in the plugin folder. If the shoutbox doesn't fit in with your theme, and you don't know how to fix up the css, I'm totally available to help. Just send me an email.

= How do I change my name from Administrator?

By default, Wordpress gives you a default nickname of "Administrator. To change this, go to your admin panel, then to Users, then change your nickname.

= I followed all of the instructions, but it doesn't find other users' messages and nothing ever fades in

This means the plugin isn't loading correctly. First off, you should check to make sure in you have javascript on. Unless you've explicitly turned it off, you should be ok. The next thing to do is check the header.php file of your theme that <?php wp_head(); ?> is somewhere in there. This shouldn't be a problem, because any theme worth its salt would preserve all wordpress hooks. Check your source code on your site and there should be a comment that says <!-- Added By Wordspew Plugin -->. That is good.

= I want users of lower user levels to be able to access the admin panel.
  
By default, you must be level 8 or higher to have access. If you want to change this, go into  wordspew.php and change the very first line of actual code (jal_admin_user_level) and change the '8' to the level you want.

= How do I change the number of posts that show up at one time?
  
Because of logistics, this is not something that is possible to set from the admin panel. There is a variable at the top of wordspew.php called $jal_number_of_comments = 35; ... Change 35 to the number you want to display

= How do I get rid of the horizontal scrollbars?

It has always somewhat mystified me why they show up in the first place. But they are easy enough to fix. Go into css.php of Wordspew and look for this line:

    padding: 6px 8px; /* Horizontal Scrollbar Killer */

Change the '8' to a higher number and the horizontal scrollbars should go away. You don't want it too high or the messages won't be very wide, so make it as low as possible while still getting rid of the scrollbars.

= I'm getting spam in WordSpew. What should I do?

All of the spam I've seen occurring in Wordspew is easy to defeat. There's a generic spambot that goes around searching for <form> elements and inputting messages. This is easy to stop with the Bad Behavior plugin. Bad Behavior is a plugin that can recognize spambots and completely block them before they even reach your site. It is also very effective at stopping comment spam, while turning up very few, if any, false positives. I HIGHLY recommend it for your site. You can grab it at http://www.ioerror.us/software/bad-behavior/ 

= How do I stop people from entering very long words in Wordspew? =

If you're getting people that type things like 'fkaosfkdsoamrlkwamrlwkfnoasdifioasfdnainfownrl' in one long string and then getting unwanted scrollbars, it should be easy to stop. Just find the jal_addData function. It should be around the 375th line of the wordspew.php file. Then look for this code:

 //	if (!preg_match("`(http|ftp)+(s)?:(//)((\w|\.|\-|_)+)(/)?(\S+)?`i", $jal_user_text, $matches))
 //		$jal_user_text = preg_replace("/([^\s]{25})/","$1 ",$jal_user_text);

and uncomment those two lines. Uncommenting simply means taking the two '/' in front of each line.

= How can I disallow/censor certain words? =

In the jal_addData function of wordspew.php (should be around line 385), there's a commented out section that says CENSORS. Uncomment the line starting with $jal_user_text . Now whenever someone writes 'fuck' it will be changed to '****' . You can add more lines like that for more censors. Also, you can filter the name field in the same way. Just change $jal_user_text to $jal_user_name in both parts of the line.

= The messages never appear =

This probably means you're using an abnormal hosting set-up. This usually occurs when you're mirroring another site and your WP url is different from the one that your actual visitors go. This should be fixed by editing wordspew.php and going to around line 100 looking for the jal_add_to_head() function. Change this:

      //$jal_wp_url = (dirname($_SERVER['PHP_SELF']) == "/") ? "/" : dirname($_SERVER['PHP_SELF']) . "/";
      $jal_wp_url = get_bloginfo('wpurl') . "/";

to:

      $jal_wp_url = (dirname($_SERVER['PHP_SELF']) == "/") ? "/" : dirname($_SERVER['PHP_SELF']) . "/";
      // $jal_wp_url = get_bloginfo('wpurl') . "/";

This problem is rooted in the fact that AJAX cannot communicate with servers outside of the domain it's running on.

= I love this plugin. How can I donate to you? =

You can paypal it over to jalenack@gmail.com . Thanks!

== Screenshots ==

1. /screenshots/admin.jpg
This is a screenshot of the Wordspew admin panel

2. /screenshots/in_action.jpg
I took this little shot of my plugin moving half-way through entering a new comment

3. /screenshots/sidebar.jpg
Here's a code sample for putting WordSpew in the sidebar

== Contact Developer ==

I am always eager to help you with your problems involving this plugin. Chances are there's something wrong with my code and by telling me you are probably helping other users too.

* Email:
	jalenack@gmail.com
* AIM:
	Harc Serf
ICQ:
	305917227
Yahoo:
	jalenack
MSN Messenger:
	jalenack@hotmail.com
* Online Contact form:
	http://blog.jalenack.com/contact.php

* = Most likely venues for a quick response
