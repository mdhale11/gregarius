<?
###############################################################################
# Gregarius - A PHP based RSS aggregator.
# Copyright (C) 2003 - 2005 Marco Bonetti
#
###############################################################################
# File: $Id$ $Name$
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
# $Log$
# Revision 1.4  2005/06/14 08:48:01  mbonetti
# extra "mark all as read" button on the front page as well
#
# Revision 1.3  2005/06/08 11:26:29  mbonetti
# (oops)
#
# Revision 1.1  2005/05/25 19:02:43  mbonetti
# Adds an extra "Mark Feed As Read" button at the bottom of each feed view
#
# Revision 1.7  2005/05/20 07:42:21  mbonetti
# CVS Log messages in the file header
#
#
###############################################################################


/// Name: Extra Button
/// Author: Marco Bonetti
/// Description: Adds an extra "Mark Feed As Read" button at the bottom of each feed view
/// Version: $Revision$

function __extra_button_Button($in) {
    list($feedCount,$unreadCount,$readCount,$itemId,$channelId) = $in;
    if ($unreadCount && $itemId == "") {
        echo "<span style=\"text-align:right\">\n";
        if ($feedCount==1) {
			markReadForm($channelId);
        } elseif ($feedCount>1) {
			markAllReadForm();
        }
        echo "</span>\n";
    }
	return null;
}

rss_set_hook('rss.plugins.items.afteritems','__extra_button_Button');
?>