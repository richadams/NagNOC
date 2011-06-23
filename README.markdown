NagNoc
======

A Nagios status monitor for a NOC or operations room.

Inspired by:
  - Naglite  (http://www.monitoringexchange.org/inventory/Utilities/AddOn-Projects/Frontends/NagLite)

  - Naglite2 (http://laur.ie/blog/2010/03/naglite2-finally-released/)
             (https://github.com/lozzd/Naglite2)
             Author: Laurie Denness <laurie@denness.net> (http://laurie.denness.net)

Forked from:
  - Naglite3 (https://saz.sh/2011/01/22/naglite3-nagios-status-monitor-for-a-noc-or-operations-room/)
             (https://github.com/saz/Naglite3)
             Author: Steffen Zieger <me@saz.sh> (http://saz.sh)

Licensed under the GPL.

Requirements
------------

Only tested with Nagios 3, but it should also work with Nagios 2.
For installation you need a webserver running PHP 5.2+.
Access to Nagios status.dat is required.

Installation
------------

1. Put index.php on your web server.
2. Edit index.php and change the path to your status.dat if required.

Customization
-------------

### CSS

If you want to change colors, they're labelled clearly in the CSS.

### Refresh interval

You can set the refresh interval (in seconds) through a GET parameter:
http://your-host/Naglite3/?refresh=100
