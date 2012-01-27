<?php
    /* this example is meant to show how to create your own RSS
    feed file with a clean url */
    

    // we want this script to publish feeds using javascript
    $type = 'js';

    // put your list name here. Otherwise, the zflist URL parameter will be used
    $listname ='sample';

    // this is the name of the template used when formatting news
    $tname = 'modern';

    // what do we want to see
    // trimtype can be 'news', 'days', 'hours'
    $trimtype = "news";
    $trimsize = 4;

    // leave this as it is
    include('newsfeeds/index.php');
?>
