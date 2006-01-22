<?php
###############################################################################
# Gregarius - A PHP based RSS aggregator.
# Copyright (C) 2003 - 2006 Marco Bonetti
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
# E-mail:      mbonetti at gmail dot com
# Web page:    http://gregarius.net/
#
###############################################################################

rss_require('util.php');
rss_require('cls/wrappers/config.php');

$GLOBALS['rss'] -> config = new Configuration();

class Configuration {

    var $_config = null;

    function Configuration() {
        $this -> _populate();
    }


    function _populate() {
        $this -> _config = null;
        $cfgQry = "select key_,value_,default_,type_,desc_,export_ "
                  ." from " .getTable("config");

        $res = rss_query($cfgQry);

        $this -> _config = array();
        while (list($key_,$value_,$default_,$type_,$description_,$export_) = rss_fetch_row($res)) {
        $value_ = real_strip_slashes($value_);
            $real_value = $this -> configRealValue($value_,$type_);
            $this -> _config[$key_] =
                array(
                    'value' => $real_value,
                    'default' => $default_,
                    'type' => $type_,
                    'description' => $description_
                );
            if ($export_ != '' && !defined($export_)) {
                define ($export_,(string)$real_value);
            }
        }

    }

    function getConfig($key,$allowRecursion = true, $invalidateCache = false) {
        if (defined('RSS_CONFIG_OVERRIDE_' . strtoupper(preg_replace('/\./','_',$key)))) {
            return constant('RSS_CONFIG_OVERRIDE_' . strtoupper(preg_replace('/\./','_',$key)));
        }

        if (array_key_exists($key,$this -> _config)) {
            return $this -> _config[$key]['value'];
        }
        elseif($allowRecursion) {
            rss_require('schema.php');
            $this -> _config = null;
            setDefaults($key);
            $this -> _populate();
            return $this -> getConfig($key,false);
        }

        return null;
    }


    function configInvalidate() {
        getConfig('dummy',true,true);
    }



    function configRealValue($value_,$type_) {
        $real_value = null;
        switch ($type_) {
        case 'boolean':
                $real_value = ($value_ == 'true');
            break;

        case 'array':
            $real_value=unserialize($value_);
            break;

        case 'enum':
            $tmp = explode(',',$value_);
            $idx = array_pop($tmp);
            $real_value = $tmp[$idx];
            break;

        case 'num':
        case 'string':
        default:
            $real_value = $value_;
            break;
        }
        return $real_value;
    }

    /**
    * Theme wrapper function to override config options
      Returns true if the config value was overridden. (otherwise it returns false)
    **/
    function rss_config_override($key, $value) {
        $confKey = 'RSS_CONFIG_OVERRIDE_' . strtoupper(preg_replace('/\./','_',$key));
        $retValue = false;
        if (!defined($confKey)) {
            define($confKey, $value);
            $retValue = true;
        }
        return $retValue;
    }

}

?>
