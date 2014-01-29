## NagNOC

_A fullscreen Nagios status monitor for a NOC or operations room._

### Requirements

* PHP
* Nagios (hosted on the same box as the webserver I'm afraid).
* A monitor dedicated for graphs, preferably hanging from the ceiling or wall.

### Installation

Put index.php on your nagios/web server. (_You may need to edit the location of the Nagios status file at the top of index.php, but no other
modifications should be needed._)

### Customization

* If you want to change the colours, they're clearly labelled in the CSS (index.php:360~)
* You can change the refresh interval through a GET parameter "?refresh=100", etc.

### Screenshots

![OK](/screenshots/01_green.png)
![Critical](/screenshots/05_hostdown.png)
![Warning](/screenshots/06_hostack_servicewarn.png)

### Credits

Forked from [Naglite3](https://github.com/saz/Naglite3) by [Steffen Zieger](http://saz.sh), which was inspired by [Naglite2](https://github.com/lozzd/Naglite2) by [Laurie Denness](http://laurie.denness.net).

