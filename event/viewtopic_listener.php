<?php
/**
	* Reactions $extends the phpBB Software package.
	* @copyright (c) 2024, Steve, https://steven-clark.tech/
	* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace steve\reactions\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class viewtopic_listener implements EventSubscriberInterface
{
	protected $auth;
	protected $config;
	protected $db;
	protected $helper;
	protected $template_context;
	protected $template;
	protected $user;
	protected $type_operator;
	protected $reactions_table;

	protected $type_ids;
	protected $post_ids;
	protected $reactions_enabled;
	protected $user_enable_reactions;
	protected $u_view_reactions;
	protected $forum_enable_reactions;
	protected $forum_reaction_type_ids;
	protected $topic_post_id;
	protected $topic_enable_reactions;
	protected $topic_locked;
	protected $poster_id;

	public function __construct(
		\phpbb\auth\auth $auth,
		\phpbb\config\config $config,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\controller\helper $helper,
		\phpbb\template\context $template_context,		
		\phpbb\template\template $template,
		\phpbb\user $user,
		\steve\reactions\reaction\reaction_types $type_operator,
		$reactions_table)
	{
		$this->auth = $auth;
		$this->config = $config;
		$this->db = $db;
		$this->helper = $helper;
		$this->template_context = $template_context;
		$this->template = $template;
		$this->user = $user;
		$this->type_operator = $type_operator;		
		$this->reactions_table = $reactions_table;

		$this->type_ids = $type_ids = [];
		$this->post_id = $post_ids = [];
	}
	
	static public function getSubscribedEvents()
	{
		return [
			'core.viewtopic_assign_template_vars_before'	=> 'template_vars_before',
			'core.viewtopic_post_rowset_data'				=> 'rowset_data',
			'core.viewtopic_cache_user_data'				=> 'user_data',
			'core.viewtopic_modify_post_data'				=> 'modify_post_data',			
			'core.viewtopic_modify_post_row'				=> 'modify_post_row',
			'core.viewtopic_post_row_after'					=> 'post_row_after',
			'core.viewtopic_modify_page_title'				=> 'topic_qr_action',
		];
	}

	public function template_vars_before($event)
	{
		$topic_data = $event['topic_data'];

		$this->reactions_enabled 		= $reactions_enabled 		= !empty($this->config['reactions_enabled']) ? true : false;
		$this->user_enable_reactions 	= $user_enable_reactions 	= !$this->auth->acl_get('u_disable_reactions') ? true : ($this->user->data['user_enable_reactions'] ? true  : false);
		$this->u_view_reactions 		= $u_view_reactions 		= $this->auth->acl_get('u_view_reactions') ? true : false;
		$this->forum_enable_reactions 	= $forum_enable_reactions 	= !empty($topic_data['forum_enable_reactions']) ? true : false;
		$this->forum_reaction_type_ids 	= $forum_reaction_type_ids 	= $topic_data['forum_reaction_type_ids'];
		$this->topic_post_id 			= $topic_post_id 			= (int) $topic_data['topic_first_post_id'];
		$this->topic_enable_reactions 	= $topic_enable_reactions 	= !empty($topic_data['topic_enable_reactions']) ? true : false;
		$this->topic_locked 			= $topic_locked 			= (empty($this->config['reactions_topic_locked']) && isset($this->config['reactions_topic_locked'])) && $topic_data['topic_status'] == ITEM_LOCKED ? true : false;
		
		$this->type_operator->tpr_common_vars([
			'S_FORUM_ENABLE_REACTIONS'		=> $this->forum_enable_reactions,
			'S_TOPIC_ENABLE_REACTIONS'		=> $this->topic_enable_reactions,
			'S_VIEWTOPIC_REACTIONS'			=> true,
			'TYPE_COUNT_ENABLE'				=> !empty($this->config['reaction_type_count_enable']) ? true : false,
		]);

		$event['topic_data'] = $topic_data;
	}

	public function rowset_data($event)
	{
		$rowset_data = $event['rowset_data'];
		$rowset_data = array_merge($rowset_data, [
			'post_disabled_reaction_ids'	=> $event['row']['post_disabled_reaction_ids'],
			'post_enable_reactions'			=> $event['row']['post_enable_reactions'],
			'post_reactions' 				=> $event['row']['post_reactions'],
			'post_reaction_data'			=> $event['row']['post_reaction_data'],
		]);
		$event['rowset_data'] = $rowset_data;
	}

	public function user_data($event)
	{
		$user_data = $event['user_cache_data'];
		$user_data = array_merge($user_data, [
			'user_disabled_reaction_ids'	=> $event['row']['user_disabled_reaction_ids'],
			'user_enable_reactions' 		=> $event['row']['user_enable_reactions'],
			'user_reactions' 				=> $event['row']['user_reactions'],
		]);
		$event['user_cache_data'] = $user_data;
	}

	public function modify_post_data($event)
	{
		$sql_where = $this->db->sql_in_set('post_id', $event['post_list']);
		if (empty($this->topic_enable_reactions))
		{
			$sql_where = "post_id = " . (int) $this->topic_post_id;
		}

		if ($this->reactions_enabled && count($event['post_list']) && $this->forum_enable_reactions && $this->user_enable_reactions && $this->u_view_reactions)
		{
			$this->reaction_types = $this->type_operator->obtain_reaction_types();
			
			if (!empty($this->reaction_types))
			{
				$this->type_ids = array_column($this->reaction_types, 'reaction_type_id');
			}

			$sql = 'SELECT reaction_user_id, reaction_type_id, post_id
				FROM ' . $this->reactions_table . "
				WHERE " . $sql_where . "
					AND reaction_user_id = " . (int) $this->user->data['user_id'];
			$result = $this->db->sql_query($sql);

			$this->user_reaction = $user_reaction = [];
			while ($row = $this->db->sql_fetchrow($result))
			{
				if (empty($row))
				{
					continue;
				}
				$this->user_reaction[(int) $row['post_id']][] = $row;
			}
			$this->db->sql_freeresult($result);
		}
	}

	public function modify_post_row($event)
	{
		if (empty($this->reactions_enabled) || empty($this->user_enable_reactions) || empty($this->u_view_reactions))
		{
			return;
		}

		$total = !empty($event['user_poster_data']['user_reactions']) ? $event['user_poster_data']['user_reactions'] : 0;

		$event['post_row'] = array_merge($event['post_row'], [
			'USER_REACTIONS' 		=> $total,
		]);

		$this->post_id = (int) $event['row']['post_id'];
		if (!$this->topic_enable_reactions && $this->post_id !=	$this->topic_post_id)
		{
			return;
		}

		$user_react = true;
		if (empty($this->config['reactions_author_react']))
		{
			$user_react = ($event['row']['user_id'] != $this->user->data['user_id']);
		}

		$post_visible = $event['row']['post_visibility'] == ITEM_APPROVED ? true : false;

		$this->poster_id = $poster_id = $event['poster_id'];
		$this->user_type_id = (int) 0;
		$this->user_reacted = false;
		if (!empty($this->user_reaction[$this->post_id]))
		{
			foreach ($this->user_reaction[$this->post_id] as $reacted)
			{
				if ($reacted['reaction_user_id'] != $this->user->data['user_id'])
				{
					continue;
				}

				$this->user_type_id = (int) $reacted['reaction_type_id'];
				if (!in_array($this->user_type_id, $this->type_ids))
				{
					continue;
				}
				$this->user_reacted = true;
			}
		}

		$this->post_reaction_data = isset($event['row']['post_reaction_data']) ? $event['row']['post_reaction_data'] : '';
		$this->post_reactions = $event['row']['post_reactions'];//select count from db
		$user_disabled_reaction_ids = isset($event['user_poster_data']['user_disabled_reaction_ids']) ? $event['user_poster_data']['user_disabled_reaction_ids'] : '';
		$this->post_disabled_reactions = $this->type_operator->disabled_reactions($this->forum_reaction_type_ids, $event['row']['post_disabled_reaction_ids'], $user_disabled_reaction_ids);

		$type_ids_post_row = (int) 0;
		if ($this->post_reaction_data && !empty($this->post_reactions))
		{
			$json_data = json_decode($this->post_reaction_data);
			if (!empty($json_data) && count($this->type_ids))
			{
				foreach ($json_data as $key => $value)
				{
					if (!is_object($value) || (!empty($this->post_disabled_reactions) && in_array($value->id, $this->post_disabled_reactions))
						|| !in_array($value->id, $this->type_ids))
					{
						continue;
					}
					$type_ids_post_row = $key + 1;
				}
			}
			unset($json_data);
		}

		$reaction_delete_url = $this->helper->route('steve_reactions_delete_reaction_controller', ['post_id' => $this->post_id, 'user_id' => $this->user->data['user_id'], 'hash' => generate_link_hash('delete_reaction')]);
		$resync_url = $this->helper->route('steve_reactions_resync_reaction_controller', ['post_id' => $this->post_id, 'user_id' => $this->user->data['user_id'], 'hash' => generate_link_hash('resync_reaction')]);
		$view_list_url = $this->helper->route('steve_reactions_view_reactions_controller_pages', ['post_id' => $this->post_id, 'reaction_id' => 0, 'hash' => generate_link_hash('get_reacted')]);

		$event['post_row'] = array_merge($event['post_row'], [
			'REFRESH'				=> (!$this->post_reaction_data && !empty($this->post_reactions)) ? true : false,

			'POST_REACTIONS' 		=> $type_ids_post_row > intval(1) && $this->post_reactions > intval(1) ? $this->type_operator->round_counts($this->post_reactions) : (int) 0,

			'S_REFRESH'				=> ($this->auth->acl_get('u_resync_reactions') && ($event['row']['user_id'] == $this->user->data['user_id'] || $this->auth->acl_get('a_', 'm_'))) ? true : false,
			'S_POST_REACTION_ENABLE'=> !empty($event['row']['post_enable_reactions']) ? true : false,
			'S_POSTER_ADD'			=> ($user_react && $this->auth->acl_get('u_add_reactions')) ? true : false,

			'U_REACTION_DELETE'		=> ($this->auth->acl_get('u_delete_reactions') && $this->user_reacted) ? $reaction_delete_url : false,
			'U_RESYNC'				=> !empty($this->config['reactions_resync_enable']) ? $resync_url : false,
			'U_VIEW_LIST'			=> ($this->auth->acl_get('u_view_post_reactions_page') && !empty($this->config['reactions_posts_page_enabled']) && $this->post_reactions > intval(1) ) ? $view_list_url : false,
		]);
	}
	
	public function post_row_after($event)
	{
		if (empty($this->reactions_enabled) || empty($this->user_enable_reactions) || empty($this->u_view_reactions) || $this->poster_id == ANONYMOUS)
		{
			return;
		}

		if (!empty($this->reaction_types) && !$this->topic_locked)
		{
			$this->type_operator->display_reaction_types($this->reaction_types, $this->post_id, $this->user_type_id, $this->post_disabled_reactions);
		}

		if ($this->post_reaction_data && !empty($this->post_reactions))
		{
			$json_data = json_decode($this->post_reaction_data);
			if (!empty($json_data))
			{
				foreach ($json_data as $key => $value)
				{
					if (!is_object($value) || (!empty($this->post_disabled_reactions) && in_array($value->id, $this->post_disabled_reactions)) || !in_array($value->id, $this->type_ids))
					{
						continue;
					}

					$view_list_url = $this->helper->route('steve_reactions_view_reactions_controller_pages', ['post_id' => $this->post_id, 'reaction_id' => $value->id, 'hash' => generate_link_hash('get_reacted')]);

					$reactions_row = [
						'ID'				=> $value->id,
						'S_REACTED'			=> $this->user_type_id == $value->id ? true : false,
						'COUNT'				=> !empty($value->count) ? $this->type_operator->round_counts($value->count) : (int) 0,
						'IMAGE_SRC'			=> $this->type_operator->get_reaction_file($value->src),
						'U_VIEW_LIST'		=> $this->auth->acl_get('u_view_post_reactions_page') && !empty($this->config['reactions_posts_page_enabled']) ? $view_list_url : false,
					];

					$this->template->assign_block_vars('postrow.reactions', $reactions_row);
				}
			}
			unset($json_data, $this->user_reaction[$this->post_id]);
		}
	}

	public function topic_qr_action($event)
	{
		if (!$this->forum_enable_reactions && !$this->user->data['is_registered'] && !$this->config['allow_quick_reply'] 
			&& (!$event['topic_data']['forum_flags'] & FORUM_FLAG_QUICK_REPLY) && !$this->auth->acl_get('f_reply', $event['forum_id']))
		{	
			return;
		}

		$root_ref = $this->template_context->get_root_ref();
		$this->template->assign_var('U_QR_ACTION', isset($root_ref['U_QR_ACTION']) ? $root_ref['U_QR_ACTION'] . '&amp;qr_action=full' : '');
		$this->template->assign_var('R_QR_ACTION', isset($root_ref['U_QR_ACTION']) ? html_entity_decode($root_ref['U_QR_ACTION'] . '&amp;qr_action=submit') : '');
	}		
}
