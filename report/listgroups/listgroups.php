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

/*
 * ----------------------------------------------------------------------
 * Original Author of file: Benoit Machiavello
 *
 * Purpose of file:
 * 		Generate group members report
 * ----------------------------------------------------------------------
 */

//Options for GLPI 0.71 and newer : need slave db to access the report
$USEDBREPLICATE=1;
$DBCONNECTION_REQUIRED=0; // not really a big SQL request

define('GLPI_ROOT', '../../../..');
include (GLPI_ROOT . "/inc/includes.php");

$report = new PluginReportsAutoReport();
//$group = new GroupCriteria($report);

$report->setColumns(array(new PluginReportsColumn('completename', $LANG["entity"][0]),
                          new PluginReportsColumnLink('groupid', $LANG["common"][35], 'Group'),
                          new PluginReportsColumnLink('userid', $LANG["setup"][18], 'User'),
                          new PluginReportsColumn('firstname', $LANG["common"][43]),
                          new PluginReportsColumn('realname', $LANG["common"][48]),
                          new PluginReportsColumnDateTime('last_login', $LANG['login'][0])));

$query = "SELECT `glpi_entities`.`completename`,
                 `glpi_groups`.`id` AS groupid,
                 `glpi_users`.`id` AS userid,
                 `glpi_users`.`firstname`,
                 `glpi_users`.`realname`,
                 `glpi_users`.`last_login`
          FROM `glpi_groups`
          LEFT JOIN `glpi_groups_users` ON (`glpi_groups_users`.`groups_id` = `glpi_groups`.`id`)
          LEFT JOIN `glpi_users` ON (`glpi_groups_users`.`users_id` = `glpi_users`.`id`
                                     AND `glpi_users`.`is_deleted` = '0' )
          LEFT JOIN `glpi_entities` ON (`glpi_groups`.`entities_id` = `glpi_entities`.`id`)".
          getEntitiesRestrictRequest(" WHERE ", "glpi_groups") ."
          ORDER BY `completename`, `glpi_groups`.`name`, `glpi_users`.`name`";

$report->setGroupBy(array('completename',
                          'groupid'));
$report->setSqlRequest($query);
$report->execute();

?>