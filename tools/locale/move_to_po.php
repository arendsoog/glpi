<?php


/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

chdir(dirname($_SERVER["SCRIPT_FILENAME"]));

if ($argv) {
   for ($i=1 ; $i<count($argv) ; $i++) {
      //To be able to use = in search filters, enter \= instead in command line
      //Replace the \= by ° not to match the split function
      $arg   = str_replace('\=', '°', $argv[$i]);
      $it    = explode("=",$arg);
      $it[0] = preg_replace('/^--/', '', $it[0]);

      //Replace the ° by = the find the good filter
      $it           = str_replace('°', '=', $it);
      $_GET[$it[0]] = $it[1];
   }
}

if (!isset($_GET['lang'])) {
   echo "Usage move_to_po.php lang=xx_YY\n Will take the pot file and try to complete it to create initial po for the lang\n";
}

define('GLPI_ROOT', '../..');
//include (GLPI_ROOT . "/inc/includes.php");

if (!is_readable(GLPI_ROOT . "/locales/".$_GET['lang'].".php")) {
   print "Unable to read dictionnary file\n";
   exit();
}
include (GLPI_ROOT . "/locales/en_GB.php");
$REFLANG=$LANG;

$lf = fopen(GLPI_ROOT . "/locales/".$_GET['lang'].".php", "r");
$lf_new = fopen(GLPI_ROOT . "/locales/temp.php", "w+");

while (($content = fgets($lf, 4096)) !== false) {
   if (!preg_match('/string to be translated/',$content,$reg)) {
      if (fwrite($lf_new, $content) === FALSE) {
         echo "unable to write in clean lang file";
         exit;
      }
   }

}
fclose($lf);
fclose($lf_new);


include (GLPI_ROOT . "/locales/temp.php");

if (!is_readable(GLPI_ROOT . "/locales/glpi.pot")) {
   print "Unable to read glpi.pot file\n";
   exit();
}
$current_string_plural = '';

$pot = fopen(GLPI_ROOT . "/locales/glpi.pot", "r");
$po  = fopen(GLPI_ROOT . "/locales/".$_GET['lang'].".po", "w+");
if ($pot && $po) {
   while (($content = fgets($pot, 4096)) !== false) {
      if (preg_match('/^msgid "(.*)"$/',$content,$reg)) {
            $current_string = $reg[1];
      }
      if (preg_match('/^msgid_plural "(.*)"$/',$content,$reg)) {
            $current_string_plural = $reg[1];
      }
      
      if (preg_match('/^msgstr[\[]*([0-9]*)[\]]* "(.*)"$/',$content,$reg)) {
            if (strlen($reg[1]) == 0) { //Singular
               $translation = search_in_dict($current_string);
               $content = "msgstr \"$translation\"\n";
            } else {

               switch ($reg[1]) {
                  case "0" : // Singular
                     $translation = search_in_dict($current_string);
                     break;
                  case "1" : // Plural
                     $translation = search_in_dict($current_string_plural);
                     break;
               }
               $content = "msgstr[".$reg[1]."] \"$translation\"\n";
            
            }
      }
     // Standard replacement
     $content = preg_replace('/charset=CHARSET/','charset=UTF-8',$content);
   
     if (preg_match('/Plural-Forms/',$content)) {
         $content = "\"Plural-Forms: nplurals=2; plural=(n != 1)\\n\"\n";
     }
      
      if (fwrite($po, $content) === FALSE) {
         echo "unable to write in po file";
         exit;
      }

   }
}
fclose($pot);
fclose($po);

function search_in_dict($string) {
   global $REFLANG, $LANG;

   $ponctmatch="[: \(\)]*";

   if (preg_match("/($ponctmatch)(.*)($ponctmatch)$/U",$string,$reg)) {
      $left   = $reg[1];
      $string = $reg[2];
      $right   = $reg[3];
   }
   foreach ($REFLANG as $mod => $data) {
      foreach ($data as $key => $val) {
         // Search same case
         if (strcmp($val,$string) === 0) {
            return $left.$LANG[$mod][$key].$right;
         } 
      }
   }
   
   foreach ($REFLANG as $mod => $data) {
      foreach ($data as $key => $val) {
         // Search non case sensitive
         if (strcasecmp($val,$string) === 0) {
            return $left.$LANG[$mod][$key].$right;
         } 
      }
   }
   
   return "";
}

?>