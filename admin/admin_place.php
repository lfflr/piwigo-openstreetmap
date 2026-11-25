<?php
/***********************************************
* File      :   admin_place.php
* Project   :   piwigo-openstreetmap
* Descr     :   Create place for reuse
*
* Created   :   07.07.2015
*
* Copyright 2013-2016 <xbgmsharp@gmail.com>
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>.
*
************************************************/

// Check whether we are indeed included by Piwigo.
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

// Check access and exit when user status is not ok
check_status(ACCESS_ADMINISTRATOR);

// Setup plugin Language
load_language('plugin.lang', OSM_PATH);

// Fetch the template.
global $template, $conf, $lang;

if (isset($_POST['add']) or isset($_POST['edit_submit']) or isset($_POST['delete']))
{
  check_pwg_token();
}

$parse_place_ids = static function (array $values) {
  $ids = array();
  foreach ($values as $value)
  {
    $id = is_string($value)
      ? filter_var($value, FILTER_VALIDATE_INT, array('options' => array('min_range' => 1, 'max_range' => 16777215)))
      : false;
    if ($id === false)
    {
      return array();
    }
    $ids[$id] = $id;
  }
  return $ids;
};

$parse_place = static function ($name, $latitude, $longitude) {
  if (!is_string($name) or trim($name) === '' or !is_string($latitude) or !is_string($longitude))
  {
    return null;
  }

  $latitude = filter_var($latitude, FILTER_VALIDATE_FLOAT);
  $longitude = filter_var($longitude, FILTER_VALIDATE_FLOAT);
  if ($latitude === false or abs($latitude) > 90 or $longitude === false or abs($longitude) > 180)
  {
    return null;
  }

  return array(
    'name' => stripslashes($name),
    'latitude' => $latitude,
    'longitude' => $longitude,
    );
};

$selected_ids = is_array($_POST['places'] ?? null) ? $parse_place_ids($_POST['places']) : array();
$edited_ids = is_string($_POST['edit_list'] ?? null) ? $parse_place_ids(explode(',', $_POST['edit_list'])) : array();

/* Table to hold osm places details */
$q = 'CREATE TABLE IF NOT EXISTS `'.osm_place_table.'` (
                `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
                `latitude` double(8,6) NOT NULL,
                `longitude` double(9,6) NOT NULL,
                `name` varchar(255) DEFAULT NULL,
                `parentId` mediumint(8),
                PRIMARY KEY (id)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8
        ;';
pwg_query($q);


// +-----------------------------------------------------------------------+
// |                                edit places                            |
// +-----------------------------------------------------------------------+

if (isset($_POST['edit_submit']) and !empty($edited_ids))
{
  $edited_ids_sql = implode(',', $edited_ids);
  $query = '
SELECT name
  FROM '.osm_place_table.'
  WHERE id NOT IN ('.$edited_ids_sql.')
;';
  $existing_names = array_from_query($query, 'name');

  $updates = array();
  foreach ($edited_ids as $place_id)
  {
    $place = $parse_place(
      $_POST['place_name-'.$place_id] ?? null,
      $_POST['place_lat-'.$place_id] ?? null,
      $_POST['place_lon-'.$place_id] ?? null
      );
    if ($place === null)
    {
      $page['errors'][] = l10n('Invalid data');
      continue;
    }

    if (in_array($place['name'], $existing_names, true))
    {
      $page['errors'][] = l10n('Place "%s" already exists', htmlspecialchars($place['name'], ENT_QUOTES, 'UTF-8'));
      continue;
    }

    $place['id'] = $place_id;
    $place['name'] = pwg_db_real_escape_string($place['name']);
    $updates[] = $place;
  }
  mass_updates(
    osm_place_table,
    array(
      'primary' => array('id'),
      'update' => array('name', 'latitude', 'longitude'),
      ),
    $updates
    );
}

// +-----------------------------------------------------------------------+
// |                               delete places                           |
// +-----------------------------------------------------------------------+

if (isset($_POST['delete']) and !empty($selected_ids))
{
  $selected_ids_sql = implode(',', $selected_ids);
  $query = '
SELECT name
  FROM '.osm_place_table.'
  WHERE id IN ('.$selected_ids_sql.')
;';
  $place_names = array_from_query($query, 'name');

  $query = '
DELETE
  FROM '.osm_place_table.'
  WHERE id IN ('.$selected_ids_sql.')
;';
  pwg_query($query);

  $page['infos'][] = l10n_dec(
    'The following place was deleted', 'The %d following places were deleted',
    count($place_names)
    )
  .' : '.htmlspecialchars(implode(', ', $place_names), ENT_QUOTES, 'UTF-8');
}

// +-----------------------------------------------------------------------+
// |                               add a place                             |
// +-----------------------------------------------------------------------+
if (isset($_POST['add']))
{
  $place = $parse_place($_POST['add_place'] ?? null, $_POST['add_lat'] ?? null, $_POST['add_lon'] ?? null);
  if ($place === null)
  {
    $page['errors'][] = l10n('Invalid data');
  }
  else
  {
    $query = "INSERT INTO `".osm_place_table."` (`name`, `latitude`, `longitude`) VALUE ('".
      pwg_db_real_escape_string($place['name'])."', '".$place['latitude']."', '".$place['longitude']."');";
    pwg_query($query);
  }
}

// all places
$query = 'SELECT * FROM `'.osm_place_table.'` order by name;';
$result = pwg_query($query);
$all_places = array();
while ($place = pwg_db_fetch_assoc($result))
{
  $all_places[] = $place;
}

// Send value to templates
$template->assign(
	array(
		'OSM_PATH' => OSM_PATH,
		'all_places' => $all_places,
	)
);

if (isset($_POST['edit']) and !empty($selected_ids))
{
  $list_name = 'EDIT_PLACES_LIST';
  if (isset($_POST['duplicate']))
  {
    $list_name = 'DUPLIC_TAGS_LIST';
  }
  elseif (isset($_POST['merge']))
  {
    $list_name = 'MERGE_TAGS_LIST';
  }

  $selected_ids_sql = implode(',', $selected_ids);
  $template->assign($list_name, $selected_ids_sql);

  $query = '
SELECT id, name, latitude, longitude
  FROM '.osm_place_table.'
  WHERE id IN ('.$selected_ids_sql.')
;';
  $result = pwg_query($query);
  while ($row = pwg_db_fetch_assoc($result))
  {
    $template->append(
      'places',
      array(
        'ID' => $row['id'],
        'NAME' => $row['name'],
        'LATITUDE' => $row['latitude'],
        'LONGITUDE' => $row['longitude'],
        )
      );
  }
}

$template->assign(array(
	'PWG_TOKEN' => get_pwg_token(),
));

?>
