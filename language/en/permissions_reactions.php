<?php
/**
	* Reactions $extends the phpBB Software package.
	* @copyright (c) 2024, Steve, https://steven-clark.tech/
	* @license GNU General Public License, version 2 (GPL-2.0)
*/

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = [];
}

$lang = array_merge($lang, [
	'ACL_CAT_REACTIONS'			=> 'Reactions',
]);

$lang = array_merge($lang, [
	'ACL_U_ADD_REACTIONS'				=> 'Can add Reactions',
	'ACL_U_CHANGE_REACTIONS'			=> 'Can change Reactions',
	'ACL_U_DELETE_REACTIONS'			=> 'Can delete Reactions',
	'ACL_U_DISABLE_REACTIONS'			=> 'Can disable Reactions Extension',
	'ACL_U_DISABLE_REACTION_TYPES'		=> 'Can disable Reaction Types to their Posts',
	'ACL_U_DISABLE_POST_REACTIONS'		=> 'Can disable Reactions to their Posts',
	'ACL_U_DISABLE_TOPIC_REACTIONS'		=> 'Can disable Reactions to Posts with in their Topics',
	'ACL_U_MANAGE_REACTIONS_SETTINGS'	=> 'Can manage UCP Reaction Settings',
	'ACL_U_RESYNC_REACTIONS'			=> 'Can re-sync Post Reactions',
	'ACL_U_VIEW_REACTIONS'				=> 'Can view Reactions',
	'ACL_U_VIEW_REACTIONS_PAGE'			=> 'Can view Reactions Page',
	'ACL_U_VIEW_POST_REACTIONS_PAGE'	=> 'Can view Post Reactions Page',
]);
