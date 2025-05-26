<?php
/**
	* Reactions $extends the phpBB Software package.
	* @copyright (c) 2024, Steve, https://steven-clark.tech/
	* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace steve\reactions\controller\ucp;

class controller
{
	protected $auth;
	protected $config;
	protected $db;
    protected $language;
	protected $request;
	protected $template;
	protected $type_operator;

	public function __construct(
		\phpbb\auth\auth $auth,
		\phpbb\config\config $config,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\language\language $language,
		\phpbb\request\request $request,
		\phpbb\template\template $template,
		\phpbb\user $user,
		\steve\reactions\reaction\reaction_types $type_operator)
	{
		$this->auth = $auth;
		$this->config = $config;
		$this->db = $db;
		$this->language = $language;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->type_operator = $type_operator;
	}

	public function ucp_reaction_settings($u_action)
	{	
		if (empty($this->config['reactions_enabled']) || !$this->auth->acl_get('u_manage_reactions_settings'))
		{
			trigger_error('MODULE_NOT_ACCESS', E_USER_WARNING);
		}

		$this->language->add_lang('ucp_reactions', 'steve/reactions');

		$marked_ary	= array_keys($this->request->variable('reaction_type_id', [0]));
		$marked = implode('|', $marked_ary);
		
		$data = [
			'user_enable_reactions' 		=> $this->request->variable('user_enable_reactions', (bool) $this->user->data['user_enable_reactions']),
			'user_enable_post_reactions' 	=> $this->request->variable('user_enable_post_reactions', (bool) $this->user->data['user_enable_post_reactions']),
			'user_enable_topic_reactions' 	=> $this->request->variable('user_enable_topic_reactions', (bool) $this->user->data['user_enable_topic_reactions']),
			'user_disabled_reaction_ids'  	=> trim($marked, '|'),
		];

		add_form_key('reactions');

		if ($this->request->is_set_post('submit'))
		{
			if (!check_form_key('reactions'))
			{
				trigger_error($this->language->lang('FORM_INVALID'));
			}

			$sql = 'UPDATE ' . USERS_TABLE . '
				SET ' . $this->db->sql_build_array('UPDATE', $data) . '
				WHERE user_id = ' . (int) $this->user->data['user_id'];
			$this->db->sql_query($sql);

			$message = $this->language->lang('UCP_REACTIONS_SAVED') . '<br><br>' . $this->language->lang('RETURN_UCP', '<a href="' . $u_action . '">', '</a>');
			trigger_error($message);
		}

		$reaction_types = $this->type_operator->obtain_reaction_types();
		$this->type_operator->display_reaction_types($reaction_types, 0, 0, explode('|', $this->user->data['user_disabled_reaction_ids']));

		$this->template->assign_vars([
			'S_UCP_ACTION'					=> $u_action,
			'S_USER_ENABLE_REACTIONS'		=> !empty($data['user_enable_reactions']) ? true : false,
			'S_USER_ENABLE_POST_REACTIONS'	=> !empty($data['user_enable_post_reactions']) ? true : false,
			'S_USER_ENABLE_TOPIC_REACTIONS'	=> !empty($data['user_enable_topic_reactions']) ? true : false,
			'U_DISABLE_REACTIONS'			=> $this->auth->acl_get('u_disable_reactions') ? true : false,
			'U_DISABLE_REACTION_TYPES'		=> $this->auth->acl_get('u_disable_reaction_types') ? true : false,
			'U_DISABLE_POST_REACTIONS'		=> $this->auth->acl_get('u_disable_post_reactions') ? true : false,
			'U_DISABLE_TOPIC_REACTIONS'		=> $this->auth->acl_get('u_disable_topic_reactions') ? true : false,
		]);
		
		$this->type_operator->tpr_common_vars();
	}
}
