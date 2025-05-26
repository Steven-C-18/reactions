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
	'UCP_REACTIONS_SETTING'			=> 'Settings',
	'UCP_REACTIONS_TITLE'			=> 'Reactions',

	'REACTIONS'						=> 'Reactions',
	'REACTIONS_TITLE'				=> 'Reactions',
	'REACTIONS_TITLES'				=> 'Reactions &bull; page %d',

	//notifications
	'NOTIFICATION_GROUP_REACTIONS'		=> 'Reactions',

	'NOTIFICATION_TYPE_POST_REACTION' 	=> 'Someone Reacts to your post',
	'POST_REACTION_NOTIFICATION'		=> '<strong>New Reaction</strong> <img src="%1$s" class="reaction-notification" alt="%2$s" /> from: %3$s In post:<br> “%4$s”',

	//Actions
	'ENABLE_POST_REACTIONS'			=> 'Enable Reactions to this post',
	'ENABLE_TOPIC_REACTIONS'		=> 'Enable Reactions in this topic',
	'EXPLAIN_REACTIONS_POSTING'		=> 'Here you can select options for topic and post Reactions',
	
	'LOG_ACP_REACTION_ADDED'		=> '<strong>Added new Reaction type %1$s</strong>',
	'LOG_ACP_REACTION_EDITED'		=> '<strong>Edited Reaction type %1$s',
	'LOG_ACP_REACTION_DELETED'		=> '<strong>Deleted Reaction type</strong>',

	'ADD_REACTION'					=> 'React to post',
	'DELETE_REACTION'				=> 'Delete Reaction',
	'REACTED'						=> 'Reacted',
	'REACTION_ADDED'				=> 'Reaction added',
	'REACTIONS_ALL_VIEWDED'			=> 'Well done, you viewed all users.',
	'REACTION_DELETED'				=> 'Reaction deleted',
	'REACTION_DUPLICATE'			=> 'You have already Reacted to this post',
	'REACTIONS_LIST_VIEW'			=> 'View All',
	'REACTIONS_LOAD_MORE'			=> 'Load More',

	'REACTION_TYPES'				=> 'Reaction Types',
	'REACTION_TYPE_DUPLICATE'		=> 'Reaction duplicate',
	'REACTION_UPDATED'				=> 'Reaction updated',
	'REACTION_RESYNC'				=> 'Re-sync',
	'RESYNC_REACTIONS'				=> 'Re-sync Reactions',
	'SELECT_REACTION_TYPES'			=> 'Select Reactions users can’t use to react to your posts',

	'UPDATE_REACTION'				=> 'Update Reaction',

	//format/round
	'REACTIONS_K'					=> 'k',
	'REACTIONS_M'					=> 'm',
	'REACTION_PERCENT'				=> '%',

	//Errors
	'NOT_AUTHORISED_REACTIONS'			=> 'You are not authorised to Reactions.',
	'REACTIONS_DISABLED'				=> 'This Reactions page is currently disabled',
	'REACTIONS_DISABLED_USER'			=> 'This Reaction can not be displayed as the user may have disabled reactions or no longer has permissions.',
	'REACTIONS_NOT_FOUND'				=> 'An <strong>Error</strong> has occurred',//?
	'REACTION_ERROR'					=> 'An <strong>Error</strong> has occurred please refresh the page and try again',
	'RESYNC_DISABLED'					=> 'Re-syncing Reactions is currently disabled',

	'USER_REACTION'	=> [
		0 => 'Reactions',
		1 => 'Reaction',
		2 => 'Reactions',
	],

	'REACTIONS_GIVEN'				=> 'Given Reactions',
	'REACTIONS_RECIEVED'			=> 'Received Reactions',
	'HR_RECENT_REACTIONS'			=> 'Recent Reactions',
	'RECENT_REACTIONS'				=> 'Showing %d Reactions of %2d',
	'REACTION_COUNT_TOTAL'			=> 'Total post Reactions',
	'REACTIONS_TOTAL'				=> 'Total Reactions',
	
	'USER_REACTION'					=> 'Reaction %d',
	'USER_REACTIONS'				=> 'Reactions %d',
	'VIEW_REACTIONS'				=> 'View Reactions',
	'VIEWING_REACTIONS'				=> 'Viewing Reactions page',
	'WELCOME_REACTIONS_PAGE'		=> 'Welcome %1$s, <br>  &nbsp  &nbsp  &nbsp  &nbsp  A total of <strong>%2$s</strong> registered users have received Reactions, you can click on the <strong>“Reaction Image”</strong> to view the received Reaction list.',

	//pre populated reactions
	'REACTION_CRY'					=> 'Cry',
	'REACTION_DISLIKE'				=> 'Dislike',
	'REACTION_FUNNY'				=> 'Funny',
	'REACTION_HAPPY'				=> 'Happy',
	'REACTION_LIKE'					=> 'Like',
	'REACTION_LOVE'					=> 'Love',
	'REACTION_MAD'					=> 'Mad',
	'REACTION_NEUTRAL'				=> 'Neutral',
	'REACTION_SAD'					=> 'Sad',
	'REACTION_SURPRISED'			=> 'Surprised',
	'REACTION_UNHAPPY'				=> 'Unhappy',
]);
