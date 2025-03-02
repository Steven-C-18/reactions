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
	'SELECT_REACTION_TYPES'				=> 'Select Reactions users can’t use to react to your posts',
	'UCP_ENABLE_REACTIONS'				=> 'Enable Reactions',
	'UCP_ENABLE_REACTIONS_EXPLAIN'		=> 'Selecting no: Will remove your ability to React, users ability to react to you, your Reactions page, counts &amp; notifications!',
	'UCP_REACTIONS_DEFAULT_POST_SETTINGS'	=> 'Edit Default Reactions settings',
	'UCP_REACTIONS_SAVED'				=> 'Reactions settings have been saved successfully!',
	'UCP_REACTIONS_SETTING'				=> 'Settings',
	'UCP_REACTIONS_TITLE'				=> 'Reactions',		
	'UCP_FOE_REACTIONS_ENABLE'			=> 'Enable Foe reactions',
	'UCP_FOE_REACTIONS_EXPLAIN'			=> 'Allow users who currently have you on their Foes list to react to you?',
	'UCP_POST_REACTIONS_ENABLE'			=> 'Enable post Reactions by default',
	'UCP_POST_REACTIONS_EXPLAIN'		=> 'Allow users to react to your posts?',
	'UCP_TOPIC_REACTIONS_ENABLE'		=> 'Enable topic Reactions by default',
	'UCP_TOPIC_REACTIONS_EXPLAIN'		=> 'Allow Reactions in your topics?',
]);
