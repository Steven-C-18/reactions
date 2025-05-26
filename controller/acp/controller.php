<?php
/**
	* Reactions $extends the phpBB Software package.
	* @copyright (c) 2024, Steve, https://steven-clark.tech/
	* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace steve\reactions\controller\acp;

class controller
{
	protected $config;
	protected $db;
    protected $language;
	protected $log;
	protected $pagination;
	protected $request;
	protected $template;
	protected $user;
	protected $tpr_delete_reactions;
	protected $type_operator;
	protected $resync;
	protected $reactions_table;
	protected $reaction_types_table;
	protected $reaction_type_id;
	protected $reactions_enabled;

	public function __construct(
		\phpbb\config\config $config,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\language\language $language,
		\phpbb\log\log $log,
		\phpbb\pagination $pagination,
		\phpbb\request\request $request,
		\phpbb\template\template $template,
		\phpbb\user $user,
		\steve\reactions\reaction\delete_reactions $tpr_delete_reactions,
		\steve\reactions\reaction\reaction_types $type_operator,
		\steve\reactions\reaction\resync_reactions $resync,
		$reactions_table,
		$reaction_types_table)
	{
		$this->config = $config;
		$this->db = $db;
		$this->language = $language;
		$this->log = $log;
		$this->pagination = $pagination;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->tpr_delete_reactions = $tpr_delete_reactions;
		$this->type_operator = $type_operator;
		$this->resync = $resync;
		$this->reactions_table = $reactions_table;
		$this->reaction_types_table = $reaction_types_table;
		$this->const = new \steve\reactions\reaction\constants;
	}

	public function get_acp_data($id, $mode, $action, $u_action)
	{
		//protected?
		$this->id = $id;
		$this->mode = $mode;
		$this->action = $action;
		$this->u_action = $u_action;
		$this->submit = $this->request->is_set_post('submit');
		$this->reaction_type_id = $reaction_type_id = $this->request->variable('reaction_type_id', 0);
		$this->reactions_enabled = $reactions_enabled = !empty($this->config['reactions_enabled']) ? true : false;
	}

	public function reaction_settings()
	{
		add_form_key('reactions');
		$errors = [];
		if (!@is_dir($this->type_operator->reactions_image_path()))
		{	
			$errors[] = $this->language->lang('ACP_REACTION_PATH_NOT_DIR', $this->config['reactions_image_path']);
		}

		if ($this->submit && empty($errors))
		{
			if (!check_form_key('reactions'))
			{
				$this->acp_error('FORM_INVALID', $this->u_action);
			}

			$this->config->set('reactions_enabled', $this->request->variable('reactions_enabled', false));
			
			$this->config->set('reactions_page_enabled', $this->request->variable('reactions_page_enabled', false));
			$this->config->set('reactions_posts_page_enabled', $this->request->variable('reactions_posts_page_enabled', false));

			$this->config->set('reactions_enable_percentage', $this->request->variable('reactions_enable_percentage', false));
			$this->config->set('reactions_enable_badge', $this->request->variable('reactions_enable_badge', false));
			$this->config->set('reactions_enable_count', $this->request->variable('reactions_enable_count', false));
			$this->config->set('reaction_type_count_enable', $this->request->variable('reaction_type_count_enable', false));
			$this->config->set('reactions_author_react', $this->request->variable('reactions_author_react', false));
			$this->config->set('reactions_resync_enable', $this->request->variable('reactions_resync_enable', false));
			$this->config->set('reactions_topic_locked', $this->request->variable('reactions_topic_locked', false));

			$this->config->set('reactions_notifications_enabled', $this->request->variable('reactions_notifications_enabled', false));
			$this->config->set('reactions_notifications_emails_enabled', $this->request->variable('reactions_notifications_emails_enabled', false));

			$this->config->set('reactions_resync_batch', $this->request->variable('reactions_resync_batch', 0));
			$this->config->set('reactions_resync_time',  $this->request->variable('reactions_resync_time', 0));
			$this->config->set('reactions_per_page', $this->request->variable('reactions_per_page', 0));
			$this->config->set('reactions_flood_time', $this->request->variable('reactions_flood_time', 0));

			$this->config->set('reaction_image_height', $this->request->variable('reaction_image_height', 0));
			$this->config->set('reaction_image_width', $this->request->variable('reaction_image_width', 0));
			$this->config->set('reactions_sql_cache', $this->request->variable('reactions_sql_cache', 0));

			$this->config->set('reactions_dropdown_width', $this->request->variable('reactions_dropdown_width', 0));
			$this->config->set('reactions_button_icon', $this->request->variable('reactions_button_icon', '', true));
			$this->config->set('reactions_button_color', str_replace('#', '', $this->request->variable('reactions_button_color', '', true)));
			$this->config->set('reactions_button_top', $this->request->variable('reactions_button_top', false));

			$this->type_operator->delete_reaction_types_cache();
			$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'ACP_REACTIONS_SETTING_UPDATED');
			$this->acp_success('ACP_REACTIONS_SETTING_SAVED', $this->u_action);
		}

		$s_errors = !empty($errors);

		$this->type_operator->tpr_common_vars([
			'REACTIONS_ENABLED'			=> $this->reactions_enabled,
			'ERROR_MSG'					=> $s_errors ? implode('<hr>', $errors) : '',
			'S_SETTINGS_MODE'			=> true,
			'S_REACTIONS_STYLESHEET'	=> true,
			'U_ACTION'					=> $this->u_action,
		]);
	}

	public function acp_reactions_main()
	{
		add_form_key('reactions');
		$start = $this->request->variable('start', 0);
		$this->type_operator->obtain_top_reaction_types('admin', $this->u_action, $this->const::TPR_ACP_LIMIT, $start);
		$reaction_types_total = $this->type_operator->reaction_type_count($this->reaction_types_table);
		$reactions_total = $this->type_operator->reactions_count($this->reactions_table);

		$this->pagination->generate_template_pagination($this->u_action, 'pagination', 'start', $reaction_types_total, $this->const::TPR_ACP_LIMIT, $start);

		$this->type_operator->tpr_common_vars([
			'U_ACTION'					=> $this->u_action,
			'U_ADD'						=> $this->u_action . '&amp;action=add',			
			'S_REACTIONS_STYLESHEET'	=> true,		
			'REACTIONS_COUNT'       	=> $reaction_types_total,
			'REACTIONS_TOTAL'			=> $this->type_operator->round_counts($reactions_total),
		]);
	}

	public function edit_add()
	{
		add_form_key('reactions');
		$errors = [];
		if ($this->action == 'edit' && empty($this->reaction_type_id))
		{
			$this->acp_error('REACTION_TYPE_ID_EMPTY', $this->u_action);
		}

		if ($this->action == 'edit')
		{
			$reaction_type = $this->type_operator->obtain_reaction_type($this->reaction_type_id);
			$total_count = $this->type_operator->reation_type_count($this->reaction_type_id);
		}

		$data = [
			'reaction_file_name' 		=> $this->request->variable('reaction_file_name', '', true),
			'reaction_type_enable'		=> $this->request->variable('reaction_type_enable', false),
			'reaction_type_title'		=> utf8_normalize_nfc($this->request->variable('reaction_type_title', '', true)),
		];

		if ($this->submit)
		{
			if (!check_form_key('reactions'))
			{
				$this->acp_error('FORM_INVALID', $this->u_action);
			}
			if ($this->action == 'add' && empty($data['reaction_file_name']))
			{
				$errors[] = $this->language->lang('ACP_NO_REACTION_IMAGE_SELECTED');
			}

			if (empty($errors))
			{
				$sql_ary = [
					'reaction_file_name'	=> (string) $data['reaction_file_name'],
					'reaction_type_enable'	=> (bool) $data['reaction_type_enable'],
					'reaction_type_title'	=> (string) $data['reaction_type_title'],
				];

				switch ($this->action) 
				{
					case $this->action == 'add':
						$sql_ary += [
							'reaction_type_order_id'	=> (int) 1,
						];

						$sql = 'INSERT INTO ' . $this->reaction_types_table . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
						$this->db->sql_query($sql);

						$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_REACTION_ADDED', false, [$data['reaction_type_title']]);
						$this->type_operator->delete_reaction_types_cache();
						$this->acp_success('ACP_REACTION_ADDED', $this->u_action);
					break;
					case $this->action == 'edit':
						$sql = 'UPDATE ' .  $this->reaction_types_table . '
							SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . '
							WHERE reaction_type_id = ' . (int) $this->reaction_type_id;
						$this->db->sql_query($sql);

						if ($reaction_type['reaction_file_name'] !== $data['reaction_file_name'] && !empty($total_count))
						{
							$this->config->set('reactions_old_file', $reaction_type['reaction_file_name']);
							$this->resync_refresh_confirm();
						}
						else
						{
							$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_REACTION_EDITED', false, [$data['reaction_type_title']]);
							$this->type_operator->delete_reaction_types_cache();
							$this->acp_success('ACP_REACTION_UPDATED', $this->u_action);
						}
					break;
		  			default:
		   				return false;
		   			break;
				}
			}
		}

		$reaction_image = isset($reaction_type['reaction_file_name']) ? $reaction_type['reaction_file_name'] : '';
		$reactions_image_path = $this->type_operator->reactions_image_path();

		$this->template->assign_vars([
			'U_ACTION'					=> $this->action == 'add' ? $this->u_action . '&amp;action=add' : $this->u_action . '&amp;action=edit&amp;reaction_type_id=' . $this->reaction_type_id,
			'S_ERROR'					=> $errors ? implode('<hr>', $errors) : '',
			'REACTION_COUNT'			=> $this->action == 'edit' && !empty($total_count) ? $this->language->lang('ACP_REACTION_TYPE_COUNT', $total_count) : false,
			'REACTIONS_ENABLED'			=> $this->reactions_enabled,
			'REACTION_PATHS'			=> $reactions_image_path,
			'S_EDIT'					=> $this->action == 'edit' ? true : false,
			'S_REACTIONS_STYLESHEET'	=> true,
			'S_FILENAME_LIST'			=> $this->type_operator->select_reaction_image($reaction_image),
			'U_ADD_REACTION'			=> true,
			'REACTION_ENABLE'			=> $this->action == 'add' ? true : (!empty($reaction_type['reaction_type_enable']) ? true : false),
			'REACTION_IMAGE'			=> $reactions_image_path . $reaction_image,
			'REACTION_TITLE'			=> isset($reaction_type['reaction_type_title']) ? $reaction_type['reaction_type_title'] : '',
		]);
	}

	public function delete_data()
	{
		if (confirm_box(true))
		{
			$this->delete_reaction_type();
		}
		else
		{
			confirm_box(false, $this->language->lang('ACP_REACTION_DELETED_CONFIRM'), build_hidden_fields([
				'i'			=> $this->id,
				'mode'		=> $this->mode,
				'action'	=> 'delete_reaction_type',
			]));
		}
	}

	public function delete_reaction_type()
	{
		@set_time_limit(0);

		$reaction_id = $this->request->variable('reaction_id', $this->reaction_type_id);
		$start_count = $this->request->variable('start_count', 0);

		if (empty($reaction_id))
		{
			$this->acp_error('REACTION_TYPE_ID_EMPTY', $this->u_action);
		}

		$this->tpr_delete_reactions->acp_delete_reaction_type($reaction_id, $this->u_action, $start_count);

		return $this;
	}

	public function resync_refresh_confirm()
	{
		if (confirm_box(true))
		{
			$this->resync_refresh();
		}
		else
		{
			confirm_box(false, $this->language->lang('ACP_REACTIONS_CONFIRM_CHANGE'), build_hidden_fields([
				'i'			=> $this->id,
				'mode'		=> $this->mode,
				'action'	=> 'resync_refresh',
			]));
		}
	}

	public function resync_refresh()
	{
		@set_time_limit(0);
		$reaction_id = $this->request->variable('reaction_id', $this->reaction_type_id);
        $start_count = $this->request->variable('start_count', 0);

		if (empty($reaction_id))
		{			
			$this->acp_error('REACTION_TYPE_ID_EMPTY', $this->u_action);
		}

		return $this->resync->resync_refresh($reaction_id, $start_count, $this->u_action);
	}
	
	public function move_up_down()
	{
		if (!check_link_hash($this->request->variable('hash', ''), 'acp_reactions') || !$this->reaction_type_id)
		{
			$this->acp_error('FORM_INVALID', $this->u_action);
		}

		$sql = 'SELECT reaction_type_order_id as order_id
			FROM ' . $this->reaction_types_table . '
			WHERE reaction_type_id = ' . (int) $this->reaction_type_id;
		$result = $this->db->sql_query($sql);
		$order_id = (int) $this->db->sql_fetchfield('order_id');
		$this->db->sql_freeresult($result);

		if ($order_id == (int) 0 && $this->action == 'move_up')
		{
			return false;
		}

		$switch_order_id = ($this->action == 'move_down') ? $order_id + (int) 1 : $order_id - (int) 1;

		$sql = 'UPDATE ' . $this->reaction_types_table . '
			SET reaction_type_order_id = ' . (int) $order_id . '
			WHERE reaction_type_order_id = ' . (int) $switch_order_id . '
				AND reaction_type_id <> ' . (int) $this->reaction_type_id;
		$this->db->sql_query($sql);
		
		$move_executed = (bool) $this->db->sql_affectedrows();

		if ($move_executed)
		{
			$sql = 'UPDATE ' . $this->reaction_types_table . '
				SET reaction_type_order_id = ' . (int) $switch_order_id . '
				WHERE reaction_type_order_id = ' . (int) $order_id . '
					AND reaction_type_id = ' . (int) $this->reaction_type_id;
			$this->db->sql_query($sql);
			
			$this->type_operator->delete_reaction_types_cache();

			return $this->response(['success' => $move_executed]);
		}

		return $this;
	}

	public function activate_deactivate()
	{
		if (!check_link_hash($this->request->variable('hash', ''), 'acp_reactions') || !$this->reaction_type_id)
		{
			$this->acp_error('FORM_INVALID', $this->u_action);
		}

		$activate_deactivate = $this->action == 'activate' ? $this->const::TPR_MAGIC_YES : $this->const::TPR_MAGIC_NO;

		$sql = 'UPDATE ' . $this->reaction_types_table . '
			SET reaction_type_enable = ' . $activate_deactivate . '
			WHERE reaction_type_id = ' . (int) $this->reaction_type_id;
		$this->db->sql_query($sql);

		$this->type_operator->delete_reaction_types_cache();
		
		return $this->response(['text' => $this->action == 'activate' ? $this->language->lang('ENABLED') : $this->language->lang('DISABLED')]);
	}

	public function sort_reaction_order()
	{
		$sql = 'SELECT reaction_type_id, reaction_type_order_id AS order_id
			FROM ' .  $this->reaction_types_table . '
			ORDER BY reaction_type_order_id';
		$result = $this->db->sql_query($sql);

		if ($row = $this->db->sql_fetchrow($result))
		{
			$order = (int) 0;
			do
			{
				++$order;
				if ($row['order_id'] != $order)
				{
					$this->db->sql_query('UPDATE ' . $this->reaction_types_table . '
						SET reaction_type_order_id = ' . $order . '
						WHERE reaction_type_id = ' . (int) $row['reaction_type_id']);

					$this->type_operator->delete_reaction_types_cache();
				}
			}
			while ($row = $this->db->sql_fetchrow($result));
		}
		$this->db->sql_freeresult($result);

		return (bool) $this->db->sql_affectedrows();
	}

	private function acp_success($lang, $url)
	{
		return trigger_error($this->language->lang($lang) . adm_back_link($url), E_USER_NOTICE);		
	}
	
	private function acp_error($lang, $url)
	{
		return trigger_error($this->language->lang($lang) . adm_back_link($url), E_USER_WARNING);		
	}
	
	private function response($data = [])
	{
		if (!$this->request->is_ajax() || !$data)
		{
			$this->acp_error('GENERAL_ERROR', $this->u_action);
		}

		$json_response = new \phpbb\json_response;
		return $json_response->send($data);
	}
}
