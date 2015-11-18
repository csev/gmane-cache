
Cache for the gmane service
---------------------------

This is a front-end to cache the content of a mailing list
hosted on gmane.org primarily to off-load their site when
some other process (i.e. 10,000 students doing their homework)
is going to pound the heck out of a particular mailing list.

In aprticular, the idea is to host this and then use something
like CloudFlare to further edge-cache the content so those 
around the world see super fast response time and the load
on the caching server is reduced as well.

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

