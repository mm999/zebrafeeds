<?php
    /* this example is meant to show how to create your own RSS
    feed file with a clean url */
    
    // we want this script to export a RSS feed
    $type='rss';

    // put your list name here
    $listname='sample';
    
    // what do we want to see
    // trimtype can be 'news', 'days', 'hours'
    $trimtype = "news";
    $trimsize = 4;
    
    include('newsfeeds/index.php');
?>
