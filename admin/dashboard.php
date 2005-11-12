<?php
###############################################################################
# Gregarius - A PHP based RSS aggregator.
# Copyright (C) 2003 - 2005 Marco Bonetti
#
###############################################################################
# This program is free software and open source software; you can redistribute
# it and/or modify it under the terms of the GNU General Public License as
# published by the Free Software Foundation; either version 2 of the License,
# or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful, but WITHOUT
# ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
# FITNESS FOR A PARTICULAR PURPOSE.	 See the GNU General Public License for
# more details.
#
# You should have received a copy of the GNU General Public License along
# with this program; if not, write to the Free Software Foundation, Inc.,
# 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA  or visit
# http://www.gnu.org/licenses/gpl.html
#
###############################################################################
# E-mail:	   mbonetti at gmail dot com
# Web page:	   http://gregarius.net/
#
###############################################################################


function dashboard() {
    $idtoken = _VERSION_ . "-" . md5($_SERVER["HTTP_HOST"]);
    $old_level = error_reporting(E_ERROR);
    $magpieCacheAge = 60*60*24;
    if (function_exists('apache_request_headers')) {
        $hdrs = apache_request_headers();
        if (
            (isset($hdrs['Pragma']) && $hdrs['Pragma']=='no-cache') ||
            (isset($hdrs['Cache-Control']) && $hdrs['Cache-Control']=='no-cache')) {
            $magpieCacheAge = 0;
        }
    }
    define ('MAGPIE_CACHE_AGE', $magpieCacheAge);
    foreach (
        array (
            'devlog_rss' => array('http://devlog.gregarius.net/feed/?db=',3),
            'plugins_rss' => array('http://plugins.gregarius.net/rss.php?db=',5),
            'themes_rss' => array('http://themes.gregarius.net/rss.php?db=',5),
            'forums_rss' => array('http://forums.gregarius.net/feeds/?Type=rss2&db=',5),
            'technorati_rss' => array('http://www.technorati.com/watchlists/rss.html?wid=59610&db=',5)
        ) as $key => $info) {
        list($url,$cnt) = $info;
        $$key=fetch_rss($url.$idtoken);
        if ($$key) {
            $$key -> items = array_slice($$key -> items, 0, $cnt);
        }
    }
    error_reporting($old_level);

    echo "
    <h2 style=\"margin-bottom: 0.5em\">Latest Gregarius News</h2>
    <div id=\"db_main\">
    <ul>
    ";

    foreach ($devlog_rss -> items as $item) {
        echo "<li class=\"item unread\">\n"
        ."<h4><a href=\"".$item['link']."\">".$item['title']."</a></h4>\n"
        ."<h5>Posted: " .time_since(strtotime($item['pubdate'])) . " ago </h5>\n"
        ."<div class=\"content\">" . $item['content']['encoded'] ."</div>\n</li>\n";
    }
    echo "</ul></div>\n";

    echo "<div id=\"db_side\" class=\"frame\">\n";
    db_side('Latest Forum posts',$forums_rss);
    db_side('Latest Plugins',$plugins_rss);
    db_side('Latest Themes',$themes_rss);
    db_side('Technorati',$technorati_rss);
    echo "</div>";
}

function db_side($title,&$rss) {
    echo "<h3>$title</h3>\n"
    ."<ul>";
    foreach ($rss -> items as $item) {
        //		var_dump($item);
        echo "<li>\n"
        ."<h5><a href=\"".htmlentities($item['link'])."\">".$item['title']."</a></h5>"
        //			."By " . $item['dc']['author'] . ", " . time_since(strtotime($item['pubdate'])) . " ago"
        ."</li>\n";
    }
    echo "</ul>\n";
}

/*
Plugin Name: Dunstan's Time Since 
Plugin URI: http://binarybonsai.com/archives/2004/08/17/time-since-plugin/
Description: Tells the time between the entry being posted and the comment being made.
Author: Michael Heilemann & Dunstan Orchard
Author URI: http://binarybonsai.com
Version: 1.0
*/
function time_since($older_date, $newer_date = false) {
    // array of time period chunks
    $chunks = array(
                  array(60 * 60 * 24 * 365 , 'year'),
                  array(60 * 60 * 24 * 30 , 'month'),
                  array(60 * 60 * 24 * 7, 'week'),
                  array(60 * 60 * 24 , 'day'),
                  array(60 * 60 , 'hour'),
                  array(60 , 'minute'),
              );

    // $newer_date will equal false if we want to know the time elapsed between a date and the current time
    // $newer_date will have a value if we want to work out time elapsed between two known dates
    $newer_date = ($newer_date == false) ? (time()) : $newer_date;

    // difference in seconds
    $since = $newer_date - $older_date;

    // we only want to output two chunks of time here, eg:
    // x years, xx months
    // x days, xx hours
    // so there's only two bits of calculation below:

    // step one: the first chunk
    for ($i = 0, $j = count($chunks); $i < $j; $i++) {
        $seconds = $chunks[$i][0];
        $name = $chunks[$i][1];

        // finding the biggest chunk (if the chunk fits, break)
        if (($count = floor($since / $seconds)) != 0) {
            break;
        }
    }

    // set output var
    $output = ($count == 1) ? '1 '.$name : "$count {$name}s";

    // step two: the second chunk
    if ($i + 1 < $j) {
        $seconds2 = $chunks[$i + 1][0];
        $name2 = $chunks[$i + 1][1];

        if (($count2 = floor(($since - ($seconds * $count)) / $seconds2)) != 0) {
            // add to output var
            $output .= ($count2 == 1) ? ', 1 '.$name2 : ", $count2 {$name2}s";
        }
    }

    return $output;
}
?>
