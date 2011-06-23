Naglite3
========

Nagios status monitor for a NOC or operations room.

Inspired by Naglite (http://www.monitoringexchange.org/inventory/Utilities/AddOn-Projects/Frontends/NagLite) 
and Naglite2 (http://laur.ie/blog/2010/03/naglite2-finally-released/).

Written by Steffen Zieger <me@saz.sh>.
Licensed under the GPL.
Modifications by Rich Adams (http://richadams.me)

In case of any problems or bug fixes, feel free to contact me.

Requirements
------------

Only tested with Nagios3, but it should also work with Nagios2.

For installation you need a Webserver running PHP 5.2+.

Access to Nagios status.dat is required.

Installation
------------

1. Place index.php in the document root of your web server.
2. Edit index.php and change the path to your status.dat if required.

Customization
-------------

### CSS

If you want to change colors, they're labelled clearly in the CSS.

### Refresh interval

You can set the refresh interval (in seconds) through a GET parameter:
http://your-host/Naglite3/?refresh=100
