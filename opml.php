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

require_once('init.php');

function getOpml($url) {
    $arr = parse_weblogsDotCom($url);
    
    if (!$arr) {
      return false;
    }else{
	return $arr;
    }
}

/**** mbi: from http://www.stargeek.com/php_scripts.php?script=20&cat=blog */

function parse_weblogsDotCom($url) {
    /*
     Grab weblogs.com list of recently updated RSS feeds
     $blogs is array() of feeds
     NAME    name
     URL        address
     WHEN        seconds since ping
     */

    
    global $blogs;
    
    $opml = getUrl($url);
    $opml = str_replace("\r", '', $opml);
    $opml = str_replace("\n", '', $opml);
    
//    var_dump($opml);
    
    $xp = xml_parser_create() or die("couldn't create parser");
    
    xml_set_element_handler($xp, '_xml_startElement', '_xml_endElement') or die ("couldnt set XML handlers");
    xml_parse($xp, $opml, true) or die ("failed parsing xml");
    xml_parser_free($xp) or die("failed freeing the parser");
    return $blogs;
}

function _xml_startElement($xp, $element, $attr) {
    global $blogs;
    if (strcasecmp('outline', $element)) {
	return;
    }
    $blogs[] = $attr;
}

function _xml_endElement($xp, $element) {
    global $blogs;
    return;
}

function getUrl($url) {
    
    $handle = fopen($url, "rb");
    $contents = "";
    do {
	$data = fread($handle, 8192);
	if (strlen($data) == 0) {
	    break;
	}
	$contents .= $data;
    } while (true);
    fclose($handle);
    
    return $contents;
}


/** OPML Export                                                          */
/** OPML2.0 RFC: http://techno-weenie.com/archives/2003/04/01/003067.php */
/** OPML1.0 Specs: http://opml.scripting.com/spec                        */

// This is a pretty lame opml 1.1 generation routine, in that it is not 
// recursive, but instead relies on the fact that we only have one level
// of folders.
// Output should be valid xml. (*fingers crossed*)
if (isset($_GET['action']) && $_GET['action'] == 'export') {
    
    $sql = "select "
      ." c.id, c.title, c.url, c.siteurl, d.name, c.parent, c.descr "
      ." from channels c, folders d "
      ." where d.id = c.parent"
      ." order by 6 asc, 2 asc";
    
    $res = rss_query($sql);
    
    $dateRes = rss_query("select max(dateadded) from channels");
    list($dateModif) = mysql_fetch_row($dateRes);
    $dateLabel = date("r", strtotime($dateModif));
    
    header("Content-Type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n"
      ."<!-- Generated by "._TITLE_. " " . _VERSION_ ." -->\n"
      ."<opml version=\"1.1\">\n";
    
    echo "\t<head>\n"
      ."\t\t<title>"._TITLE_." OPML Feed</title>\n"
      ."\t\t<dateModified>$dateLabel</dateModified>\n"
      ."\t</head>\n"
      ."\t<body>\n";
    
    $prev_parent=0;
    while (list($id, $title, $url, $siteurl, $name, $parent, $descr) = mysql_fetch_row($res)) {
	$descr_ = htmlspecialchars ($descr);
	$title_ = htmlspecialchars($title);
	$url_ = htmlspecialchars($url);
	$siteurl_ = htmlspecialchars($siteurl);
	$name_ = htmlspecialchars($name);
	
	if ($parent != $prev_parent) {
	    if ($prev_parent != 0) {
		echo "\t\t</outline>\n";
	    }
	    $prev_parent = $parent;
	    echo "\t\t<outline text=\"$name_\">\n";
	}
	
	if ($parent > 0) 
	  echo "\t";
	
	echo "\t\t<outline text=\"$title_\" description=\"$descr_\" type=\"rss\"";
	if ($siteurl != "") {
	    echo " htmlUrl=\"$siteurl_\"";
	}
	
	echo " xmlUrl=\"$url_\" />\n";
    }
    
    if ($prev_parent > 0) {
	echo "\t</outline>\n";
    }
    
    echo "\t</body>\n</opml>\n";
}
?>
