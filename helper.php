<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_tags_popular
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Helper for mod_tags_popular
 *
 * @package     Joomla.Site
 * @subpackage  mod_tags_popular
 * @since       3.1
 */
abstract class ModTagsselectedHelper
{
	public static function getContentList($params)
	{
		$db         = JFactory::getDbo();
		$app        = JFactory::getApplication();
		$user       = JFactory::getUser();
		$groups     = implode(',', $user->getAuthorisedViewLevels());
		//$matchtype  = $params->get('matchtype', 'all');
		$maximum    = $params->get('maximum', 5);
		$tagsHelper = new JHelperTags;
		$option     = $app->input->get('option');
		$view       = $app->input->get('view');
		$prefix     = $option . '.' . $view;
		$id         = (array) $app->input->getObject('id');
		$selectedTag = $params->get('selected_tag');
		// Strip off any slug data.
		foreach ($id as $id)
		{
			if (substr_count($id, ':') > 0)
			{
				$idexplode = explode(':', $id);
				$id        = $idexplode[0];
			}
		}

			$tagsToMatch = $selectedTag;
			if (!$tagsToMatch || is_null($tagsToMatch))
			{
				return $results = false;
			}

			$tagCount = substr_count($tagsToMatch, ',') + 1;

			$query = $db->getQuery(true)
				->select(
				array(
					$db->quoteName('m.tag_id'),
					$db->quoteName('m.core_content_id'),
					$db->quoteName('m.content_item_id'),
					$db->quoteName('m.type_alias'),
						'COUNT( '  . $db->quoteName('tag_id') . ') AS ' . $db->quoteName('count'),
					$db->quoteName('t.access'),
					$db->quoteName('t.id'),
					$db->quoteName('ct.router'),
					$db->quoteName('cc.core_title'),
					$db->quoteName('cc.core_alias'),
					$db->quoteName('cc.core_catid'),
					$db->quoteName('cc.core_language')
					)
			);

			$query->from($db->quoteName('#__contentitem_tag_map', 'm'));

			$query->join('INNER', $db->quoteName('#__tags', 't') . ' ON m.tag_id = t.id')
				->join('INNER', $db->quoteName('#__ucm_content', 'cc') . ' ON m.core_content_id = cc.core_content_id')
				->join('INNER', $db->quoteName('#__content_types', 'ct') . ' ON m.type_alias = ct.type_alias');

			$query->where('t.access IN (' . $groups . ')');
			$query->where($db->quoteName('m.tag_id') . ' IN (' . $tagsToMatch . ')');


			// Only return published tags
			$query->where($db->quoteName('cc.core_state') . ' = 1 ');

			// Optionally filter on language
			$language = JComponentHelper::getParams('com_tags')->get('tag_list_language_filter', 'all');

			if ($language != 'all')
			{
				if ($language == 'current_language')
				{
					$language = JHelperContent::getCurrentLanguage();
				}
				$query->where($db->quoteName('cc.core_language') . ' IN (' . $db->quote($language) . ', ' . $db->quote('*') . ')');
			}

			$query->group($db->quoteName(array('m.core_content_id')));
			if ($tagCount > 0)
			{
				$query->having('COUNT( '  . $db->quoteName('tag_id') . ')  = ' . $tagCount);
			
			}

			$query->order($db->quoteName('count') . ' DESC');
			$db->setQuery($query, 0, $maximum);
			$results = $db->loadObjectList();

			foreach ($results as $result)
			{
				$explodedAlias = explode('.', $result->type_alias);
				$result->link = 'index.php?option=' . $explodedAlias[0] . '&view=' . $explodedAlias[1] . '&id=' . $result->content_item_id . '-' . $result->core_alias;
			}

			return $results;
		
	}
}