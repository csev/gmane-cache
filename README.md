
Cache for the gmane service
---------------------------

This is a front-end to cache the content of a mailing list
hosted on gmane.org primarily to off-load their site when
some other process (i.e. 10,000 students doing their homework)
is going to pound the heck out of a particular mailing list.

You can play with an implementation of this at URLs like

http://gmane.dr-chuck.net/gmane.comp.cms.sakai.devel/12/13

Where 12 and 13 are a range of message numbers.  This caches the 
gmane content in a MySQL database on my 1and1 ISP and then the URLs 
are further cached using my CloudFlare account.  You can compare 
this to looking at the original from gmane at:

http://download.gmane.org/gmane.comp.cms.sakai.devel/12/13

My cached copy scales very nicely and is much quicker once the
messages have been retrieved once from gmane to my 1and1 database.

For fun, take a look at the developers console on my cached copy - 
I have a little  response header in there to show what is happening 
behind the scenes.

Configuration
-------------

Copy the *config-dist.php* to *config.php* and edit to set up
the database tabel and various settings:

    $CFG = new stdClass();

    $CFG->pdo       = 'mysql:host=127.0.0.1;port=8889;dbname=gmane'; // MAMP
    $CFG->dbuser    = 'fred';
    $CFG->dbpass    = 'zap';

    $CFG->expire = 7*24*60*60;  // A week
    $CFG->maxtext = 200000;

    // Only add these at the end and keep the same order unless
    // you completely empty out the messages table.
    $ALLOWED = array(
        'gmane.comp.cms.sakai.devel'
    );
