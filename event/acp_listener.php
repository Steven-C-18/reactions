<?php
/**
	* Reactions $extends the phpBB Software package.
	* @copyright (c) 2024, Steve, https://steven-clark.tech/
	* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace steve\reactions\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class acp_listener implements EventSubscriberInterface
{
	protected $request;
	protected $type_operator;

	public function __construct(
		\phpbb\request\request $request,
		\steve\reactions\reaction\reaction_types $type_operator)
	{
		$this->request = $request;
		$this->type_operator = $type_operator;
	}
	
	static public function getSubscribedEvents()
	{
		return [
			'core.permissions'							=> 'add_permission',
			'core.acp_manage_forums_request_data'		=> 'forums_request_data',
			'core.acp_manage_forums_initialise_data'	=> 'forums_initialise_data',
			'core.acp_manage_forums_display_form'		=> 'forums_display_form',
		];
	}

	public function add_permission($event)
	{
		$event['categories'] = array_merge($event['categories'], [
			'reactions'	=> 'ACL_CAT_REACTIONS',
		]);

		$new_permissions = [
			'u_view_reactions' 				=> ['lang' => 'ACL_U_VIEW_REACTIONS', 				'cat' => 'reactions'],
			'u_view_reactions_pages' 		=> ['lang' => 'ACL_U_VIEW_REACTIONS_PAGE', 			'cat' => 'reactions'],
			'u_view_post_reactions_page'	=> ['lang' => 'ACL_U_VIEW_POST_REACTIONS_PAGE',		'cat' => 'reactions'],
			'u_add_reactions' 				=> ['lang' => 'ACL_U_ADD_REACTIONS', 				'cat' => 'reactions'],
			'u_change_reactions' 			=> ['lang' => 'ACL_U_CHANGE_REACTIONS', 			'cat' => 'reactions'],			
			'u_delete_reactions'	 		=> ['lang' => 'ACL_U_DELETE_REACTIONS', 			'cat' => 'reactions'],
			'u_resync_reactions'			=> ['lang' => 'ACL_U_RESYNC_REACTIONS',				'cat' => 'reactions'],
			'u_disable_reactions'			=> ['lang' => 'ACL_U_DISABLE_REACTIONS', 			'cat' => 'reactions'],
			'u_disable_post_reactions' 		=> ['lang' => 'ACL_U_DISABLE_POST_REACTIONS', 		'cat' => 'reactions'],
			'u_disable_topic_reactions' 	=> ['lang' => 'ACL_U_DISABLE_TOPIC_REACTIONS', 		'cat' => 'reactions'],
			'u_disable_reaction_types'		=> ['lang' => 'ACL_U_DISABLE_REACTION_TYPES', 		'cat' => 'reactions'],
			'u_manage_reactions_settings'	=> ['lang' => 'ACL_U_MANAGE_REACTIONS_SETTINGS',	'cat' => 'reactions'],
		];

		$event['permissions'] = array_merge($event['permissions'], $new_permissions);
	}

	public function forums_request_data($event)
	{
		$marked_ary	= array_keys($this->request->variable('forum_reaction_type_ids', [0]));
		$marked = implode('|', $marked_ary);

		$forum_data = $event['forum_data'];
		$forum_data['forum_enable_reactions'] = $this->request->variable('forum_enable_reactions', false);
		$forum_data['forum_reaction_type_ids'] = $marked;
		$event['forum_data'] = $forum_data;
	}

	public function forums_initialise_data($event)
	{
 		$forum_data = $event['forum_data'];
		if ($event['action'] == 'add' && !$event['update'])
		{
			$forum_data['forum_enable_reactions'] = true;
			$forum_data['forum_reaction_type_ids'] = '';
		}
		$event['forum_data'] = $forum_data;
	}

	public function forums_display_form($event)
	{
		$marked = explode('|', $event['forum_data']['forum_reaction_type_ids']);
		$reaction_types = $this->type_operator->obtain_reaction_types();
		$this->type_operator->display_reaction_types($reaction_types, 0, 0, $marked);

		$template_data = $event['template_data'];
		$template_data = array_merge($template_data, [
			'S_ENABLE_REACTIONS'	=> $event['forum_data']['forum_enable_reactions'],
		]);
		$event['template_data'] = $template_data;
	}
}
