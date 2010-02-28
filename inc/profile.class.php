<?php

/*
  ----------------------------------------------------------------------
  GLPI - Gestionnaire Libre de Parc Informatique
  Copyright (C) 2003-2008 by the INDEPNET Development Team.

  http://indepnet.net/   http://glpi-project.org/
  ----------------------------------------------------------------------

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
  along with GLPI; if not, write to the Free Software
  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
  ------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Dévi Balpe
// Purpose of file:
// ----------------------------------------------------------------------

class PluginReportsProfile extends CommonDBTM {


   //if profile deleted
   static function cleanProfiles(Profile $prof) {
      $plugprof = new self();
      $plugprof->delete(array('id'=>$prof->getField("id")));
   }

   function canCreate() {
      return haveRight('profile', 'w');
   }

   function canView() {
      return haveRight('profile', 'r');
   }


   function showForm($id, $options=array()){
      global $LANG,$DB;

      $target = $this->getFormURL();
      if (isset($options['target'])) {
        $target = $options['target'];
      }

      if ($id > 0){
         $this->check($id,'r');
      } else {
         $this->check(-1,'w');
      }

      $canedit=$this->can($id,'w');

      echo "<form action='".$target."' method='post'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='4' class='center b'>".
             $LANG['plugin_reports']['config'][4]." ".$this->fields["profile"]."</th></tr>";

      foreach(searchReport() as $key => $plug) {
         $mod = ($plug=='reports' ? $key : "${plug}_${key}");
         echo "<tr class='tab_bg_1'>";
         echo "<td>$plug</td>";
         if (strpos($key,'stat') === false) {
            echo "<td>".$LANG['Menu'][6]."</td>";
         } else {
            echo "<td>".$LANG['Menu'][13]."</td>";
         }
         echo "<td>".$LANG["plugin_$plug"][$key][1]." :</td><td>";
         Profile::dropdownNoneReadWrite($mod,(isset($this->fields[$mod])?$this->fields[$mod]:''),1,1,0);
         echo "</td></tr>";
      }

      if ($canedit) {
         echo "<tr class='tab_bg_1'>";
         echo "<td class='center' colspan='4'>";
         echo "<input type='hidden' name='id' value=$id>";
         echo "<input type='submit' name='update_user_profile' value='".
                $LANG['buttons'][7]."' class='submit'>";
         echo "</td></tr>\n";
      }
      echo "</table></form>";
   }


   function updateRights($reports) {
      global $DB;

      $rights = array();
      foreach($reports as $report => $plug) {
         if ($plug =='reports') {
            $rights[$report]=1;
         } else {
            $rights["${plug}_${report}"]=1;
         }
      }
      // Add missing profiles
      $DB->query("INSERT INTO
                  `".$this->getTable()."` (`id`, `profile`)
                  (SELECT `id`, `name`
                   FROM `glpi_profiles`
                   WHERE `id` NOT IN (SELECT `id`
                                      FROM `".$this->getTable()."`))");

      $current_rights = $this->fields;
      unset($current_rights["id"]);
      unset($current_rights["profile"]);
      foreach($current_rights as $right => $value) {
         if (!isset($rights[$right])) {
            // Delete the columns for old reports
            $DB->query("ALTER TABLE
                        `".$this->getTable()."`
                        DROP COLUMN `".$right."`");
         } else {
            unset($rights[$right]);
         }
      }

      foreach ($rights as $key=>$right) {
         // Add the column for new report
         $DB->query("ALTER TABLE
                     `".$this->getTable()."`
                     ADD COLUMN `".$key."` char(1) DEFAULT NULL");
         // Add "read" write to Super-admin
         $DB->query("UPDATE
                     `".$this->getTable()."`
                     SET `".$key."`='r'
                     WHERE `id` = '4'");
      }

      // Delete unused profiles
      $DB->query("DELETE
                  FROM `".$this->getTable()."`
                  WHERE `id` NOT IN (SELECT `id`
                                     FROM `glpi_profiles`)");
   }


   static function changeprofile() {

      $prof = new self();
      if ($prof->getFromDB($_SESSION['glpiactiveprofile']['id'])) {
         $_SESSION["glpi_plugin_reports_profile"]=$prof->fields;
      } else {
         unset($_SESSION["glpi_plugin_reports_profile"]);
      }
   }


   /**
    * Create access rights for an user
    * @param id the user id
    */
   function createaccess($id) {
      global $DB;

      $Profile = new Profile();
      $Profile->GetfromDB($id);
      $name = $Profile->fields["profil"];

      $query = "INSERT INTO
                `".$this-getTable()."` (`id`, `profile`)
                VALUES ('$id', '$name');";
      $DB->query($query);
   }


   /**
    * Look for all the plugins, and update rights if necessary
    */
   function updatePluginRights() {

      $this->getEmpty();
      $tab = searchReport();
      $this->updateRights($tab);

      return $tab;
   }


}

?>