<?
###############################################################################
# Gregarius - A PHP based RSS aggregator.
# Copyright (C) 2003, 2004 Marco Bonetti
#
###############################################################################
# This program is free software and open source software; you can redistribute
# it and/or modify it under the terms of the GNU General Public License as
# published by the Free Software Foundation; either version 2 of the License,
# or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful, but WITHOUT
# ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
# FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
# more details.
#
# You should have received a copy of the GNU General Public License along
# with this program; if not, write to the Free Software Foundation, Inc.,
# 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA  or visit
# http://www.gnu.org/licenses/gpl.html
#
###############################################################################
# E-mail:      mbonetti at users dot sourceforge dot net
# Web page:    http://sourceforge.net/projects/gregarius
#
###############################################################################



function rss_header($title="", $active=0) {
    echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\" "
      ."\"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">\n"
      ."<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\">\n"
      ."<head>\n"
      ."\t<meta http-equiv=\"Content-Type\" content=\"text/html; "
      ."charset=ISO-8859-1\" />\n"
      ."\t<title>".makeTitle($title)."</title>\n"
      ."\t<meta name=\"robots\" content=\"NOINDEX,NOFOLLOW\"/>\n"
      ."\t<link rel=\"stylesheet\" type=\"text/css\" href=\"". getPath() ."css/css.css\"/>\n";

    if ($active == 1 && defined('RELOAD_AFTER') 
    /* && RELOAD_AFTER > (30*MINUTE) */) {
	echo "\t<meta http-equiv=\"refresh\" "
	  ." content=\"" . RELOAD_AFTER
	  . ";url=update.php\"/>\n";
    }

    echo "</head>\n"
      ."<body>\n";
    nav($title,$active);
}

function rss_footer() {
    ftr();
    echo "</body>\n"
      ."</html>\n";
}

function rss_query ($query) {
    $ret = mysql_query($query) or die ("failed to execute \"$query\": " .mysql_error());
    return $ret;
}

function nav($title, $active=0) {
    echo "\n<div id=\"nav\" class=\"frame\">"
      ."\n<a id=\"top\"></a>\n"
      ."\n<h1>".makeTitle($title)."</h1>\n"
      ."<ul id=\"navlist\">\n"
      ."\t<li".($active==1?" class=\"active\"":"")."><a href=\"". getPath() ."index.php\">".NAV_HOME."</a></li>\n"
      . "\t<li".($active==2?" class=\"active\"":"")."><a href=\"". getPath() ."update.php\">" . NAV_UPDATE. "</a></li>\n"      
      . "\t<li".($active==3?" class=\"active\"":"")."><a href=\"". getPath() ."search.php\">".NAV_SEARCH."</a></li>\n"
      . "\t<li".($active==4?" class=\"active\"":"")."><a href=\"". getPath() ."channel_admin.php\">".NAV_CHANNEL_ADMIN ."</a></li>\n"
      . "</ul>\n</div>\n";
}



function ftr() {
    echo "\n<div id=\"footer\" class=\"frame\">\n";
    echo "<span>\n\t<a href=\"#top\">TOP</a>\n</span>\n";

    echo "<span>\n\t"._TITLE_ . " " ._VERSION_ . " " . FTR_POWERED_BY . "<a href=\"http://php.net\">PHP</a>, \n"
      ."\t<a href=\"http://magpierss.sourceforge.net/\">MagpieRSS</a>, \n"
      ."\t<a href=\"http://sourceforge.net/projects/kses\">kses</a> \n"
      ."\tand love\n</span>\n";

    echo "<span>\n\tBest effort valid <a href=\"http://validator.w3.org/check/referer\">XHTML1.1</a>, \n"
      ."\t<a href=\"http://jigsaw.w3.org/css-validator/check/referer\">CSS2.0</a>\n</span>\n";

    echo "<span>\n\tPage rendered ". date(DATE_FORMAT)."\n</span>\n";

    echo "</div>\n\n";
}

function rss_error($message) {
    echo "\n<p class=\"error\">$message</p>\n";
}

function add_channel($url) {
    assert($url != "" && strlen($url) > 7);
    $urlDB = htmlentities($url);

    $rss = fetch_rss( $url );
    /*
     *          echo "<pre>";
     *          var_dump($rss);
     *          echo "</pre>";
     * */
    if ( $rss ) {
        $title= mysql_real_escape_string ( $rss->channel['title'] );
        $siteurl= mysql_real_escape_string (htmlentities($rss->channel['link'] ));
        $descr =  mysql_real_escape_string ($rss->channel['description']);
    
        //lets see if this server has a favicon
        $icon = "";
        if (_USE_FAVICONS_ && $rss->channel['link']  != "") {
            $match = get_host($rss->channel['link'], $host);
            $uri = "http://" . $host . "favicon.ico";
            if ($match && (getHttpResponseCode($uri)))  {
              $icon = $uri;
            }
        }
    
        if ($title != "") {
            $sql = "insert into channels (title, url, siteurl, parent, descr, dateadded, icon)"
              ." values ('$title', '$urlDB', '$siteurl', 0, '$descr', now(), '$icon')";
    
            rss_query($sql);
        } else {
            rss_error ("I'm sorry, I couldn't extract a valid RSS feed from <a href=\"$url\">$url</a>.");
        }
    } else {
        rss_error( "I'm sorry, I could'n retrieve <a href=\"$url\">$url</a>.");
    }
}

/** this functions checks wether a URI exists */
function getHttpResponseCode($forUri) {
    $fp = @fopen($forUri, "rb");
    return (($fp)?true:false);
}

// basically strips folder resources from URIs.
// http://pear.php.net/package/HTTP_Client/ --> http://pear.php.net/
function get_host($url, & $host) {
    $ret = preg_match("/^(http:\/\/)?([^\/]+)/i", $url, $matches);
    $host = $matches[2];

    //ensure we have a slash
    if (substr($host,-1) != "/") {
      $host .= "/";
    }
    
    return $ret;
}

function makeTitle ($title) {
    $ret = ""._TITLE_ . "";
    if ($title != "") {
        $ret .= " &gt; " . $title;
    }

    return $ret;
}

/*** update the given feed(s) **/

function update($id) {
    global $kses_allowed;

    $sql = "select id, url, title from channels";
    if (is_numeric($id)) {
        $sql .= " where id=$id";
    }

    $res = rss_query($sql);
    while (list($cid, $url, $title) = mysql_fetch_row($res)) {
        $rss = fetch_rss( $url );
	/*
	echo "<pre>";
	var_dump ($rss);
	echo "</pre>";
	*/
	
	if (array_key_exists('link', $rss->channel)) {
	    $baseUrl = $rss->channel['link'];
	}
        foreach ($rss->items as $item) {
	    	   	    	    
            $title = $item['title'];
     
	    if (array_key_exists('content',$item) && array_key_exists('encoded', $item['content'])) {
		$description = kses($item['content']['encoded'], $kses_allowed);
	    } elseif (array_key_exists ('description', $item)) {
		$description = kses($item['description'], $kses_allowed);
	    } else {
		$description = "";
	    }
	    
	    if ($description != "" && $baseUrl != "") {
		$description = make_abs($description, $baseUrl);
	    }
	    
	    if (array_key_exists('link',$item) && $item['link'] != "") {
		$url = $item['link'];
	    } elseif (array_key_exists('guid',$item) && $item['guid'] != "") {
		$url = $item['guid'];
	    } else {
		// fall to something basic
		$url = md5($title);
	    }
	    
	    
	    $cDate = -1;	    	    
	    if (array_key_exists('dc',$item) && array_key_exists('date',$item['dc'])) {
		// RSS 1.0 
		$cDate =  parse_w3cdtf($item['dc']['date']);
	    } elseif (array_key_exists('pubdate',$item)) {
		// RSS 2.0 (?)
		$cDate = strtotime ($item['pubdate']);
	    } elseif (array_key_exists('created',$item)) {
		// atom
		$cDate = parse_iso8601 ($item['created']);
	    }

	    
            $sql = "select id from item where cid=$cid and url='$url'";
            $subres = rss_query($sql);
            list($indb) = mysql_fetch_row($subres);
	    
	    if ($cDate > 0) {
		$sec = "FROM_UNIXTIME($cDate)";		
	    } else {
		$sec = "null";
	    }
	    
            if ($indb == "") {							
                $sql = "insert into item (cid, added, title, url, "
                  ." description, unread, pubdate) "
                  . " values ("
                  ."$cid, now(), '"
                  .mysql_real_escape_string($title) ."', '$url', '"
                  . mysql_real_escape_string($description)
                    ."', 1, $sec)";
        
                rss_query($sql);
            }
        }
    }
}

/**
 * renders a list of items. Returns the number of items actually shown
 */
function itemsList ($title,$items){

    echo "\n\n<h2>$title</h2>\n";
    
    
    $cntr=0;
    $prev_cid=0;
    
    $ret = 0;
    
    while (list($row, $item) = each($items)) {
    	
    	list($cid, $ctitle,  $cicon, $ititle, $iunread, $iurl, $idescr, $ts) = $item;
        
        if ($prev_cid != $cid) {
            $prev_cid = $cid;
            if ($cntr++ > 0)
              echo "</ul>\n";

	    if ($ctitle != "" && $cid > -1) {
		echo "<h3>";
		if (_USE_FAVICONS_ && $cicon != "") {
		    echo "<img src=\"$cicon\" class=\"favicon\" alt=\"\"/>";
		}
		
		if (_USE_MODREWRITE_) {
		    echo "<a href=\"" .getPath() ."$ctitle/\">$ctitle</a>";
		} else {
		    echo "<a href=\"". getPath() ."feed.php?cid=$cid\">$ctitle</a>";
		}
		echo "</h3>\n";
	    }
	    
            echo "<ul>\n";
        }

        
        $cls="item";
        if (($cntr++ % 2) == 0) {
            $cls .= " even";
        } else {
            $cls .= " odd";
        }
        
        if  ($iunread == 1) {
            $cls .= " unread";
        }
        
        $url = htmlentities($iurl);
	// some url fields are juste guid's which aren't actual links
	$isUrl = (substr($url, 0,4) == "http");
        echo "\t<li class=\"$cls\">\n"
          ."\t\t<h4>";
	
	if ($isUrl) echo "<a href=\"$url\">";
	
	//echo htmlentities($ititle);
	echo $ititle;
	
	if ($isUrl) echo "</a>";
	  
	echo "</h4>\n";
        
	if ($ts != "") {
	    echo "\t\t<h5>". POSTED . date(DATE_FORMAT, $ts). "</h5>\n";
	}
	
	
        if ($idescr != "" && trim(str_replace("&nbsp;","",$idescr)) != "") {
            echo "\t\t<div class=\"content\">$idescr</div>\n";
        }        
        echo "\t</li>\n";
	$ret++;
    }
    echo "</ul>\n";
    
    return $ret;
}

/**
 * renders links in $in absolute, based on $base.
 * The regexp probably needs tuning.
 */
function make_abs($in, $base) {
    $pattern = "/(<a href=\")([^http](\/)?.*?)\">/im";
    $repl = "<a href=\"". $base. "\\2\">";
    return preg_replace($pattern, $repl, $in);
}


/**
 * parse an ISO 8601 date, losely based on parse_w3cdtf from MagpieRSS 
 */
function parse_iso8601 ( $date_str ) {    
    # regex to match wc3dtf
    $pat = "/(\d{4})-?(\d{2})-?(\d{2})T?(\d{2}):?(\d{2})(:?(\d{2}))?(?:([-+])(\d{2}):?(\d{2})|(Z))?/";
    
    if ( preg_match( $pat, $date_str, $match ) ) {
	list( $year, $month, $day, $hours, $minutes, $seconds) =
	  array( $match[1], $match[2], $match[3], $match[4], $match[5], $match[6]);
	
	# calc epoch for current date assuming GMT
	$epoch = gmmktime( $hours, $minutes, $seconds, $month, $day, $year);
	
	$offset = 0;
	if ( $match[10] == 'Z' ) {
	    # zulu time, aka GMT
	}
	else {
	    list( $tz_mod, $tz_hour, $tz_min ) =
	      array( $match[8], $match[9], $match[10]);
	    
	    # zero out the variables
	    if ( ! $tz_hour ) { $tz_hour = 0; }
	    if ( ! $tz_min ) { $tz_min = 0; }
	    
	    $offset_secs = (($tz_hour*60)+$tz_min)*60;
	    
	    # is timezone ahead of GMT?  then subtract offset
	    #
	    if ( $tz_mod == '+' ) {
		$offset_secs = $offset_secs * -1;
	    }
	    
	    $offset = $offset_secs;
	}
	$epoch = $epoch + $offset;
	return $epoch;
    }
    else {
	return -1;
    }
}

function getPath() {
    static $ret;
    $ret = dirname($_SERVER['PHP_SELF']) . "/";
    
    return $ret;
}
?>
