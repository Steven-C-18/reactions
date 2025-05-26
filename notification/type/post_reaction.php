<?php
/**
	* Reactions $extends the phpBB Software package.
	* @copyright (c) 2024, Steve, https://steven-clark.tech/
	* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace steve\reactions\notification\type;

class post_reaction extends \phpbb\notification\type\base
{
	public function get_type()
	{
		return 'steve.reactions.notification.type.post_reaction';
	}

	public static $notification_option = [
		'group'	=> 'NOTIFICATION_GROUP_REACTIONS',
		'lang'	=> 'NOTIFICATION_TYPE_POST_REACTION',
	];

	protected $helper;
	protected $user_loader;
	protected $config;
		
	public function set_config(\phpbb\config\config $config)
	{
		$this->config = $config;
	}

	public function set_user_loader(\phpbb\user_loader $user_loader)
	{
		$this->user_loader = $user_loader;
	}

	public function set_controller_helper(\phpbb\controller\helper $helper)
	{
		$this->helper = $helper;
	}

	public function is_available()
	{
		return ($this->config['reactions_enabled'] && $this->config['reactions_notifications_enabled']) ? true : false;
	}

	public static function get_item_id($data)
	{
		return (int) $data['reaction_id'];
	}

	public static function get_item_parent_id($data)
	{
		return (int) $data['post_id'];
	}

	public function find_users_for_notification($data, $options = [])
	{
		$options = array_merge([
			'ignore_users'		=> [],
		], $options);
		
		$users = [$data['poster_id']];

		if (empty($users))
		{
			return [];
		}

		return $this->check_user_notification_options($users, $options);
	}

	public function users_to_query()
	{
		return [$this->get_data('user_id')];
	}

 	public function get_avatar()
	{
		return $this->user_loader->get_avatar($this->get_data('user_id'), true, true);
	}

	public function get_title()
	{
		$reaction = generate_board_url() . '/' . $this->config['reactions_image_path'] . '/' . $this->get_data('reaction_file_name');
		$title = $this->language->lang('POST_REACTION_NOTIFICATION', $reaction, $this->get_data('reaction_type_title'), $this->user_loader->get_username($this->get_data('user_id'), 'no_profile'), $this->get_data('post_subject'));
		
		return $title;
	}

	public function get_url()
	{
		$post_id = $this->get_data('post_id');
		return append_sid($this->phpbb_root_path . 'viewtopic.' . $this->php_ext . '?p=' . $post_id . '#p' . $post_id);
	}

	public function get_email_template()
	{
		if (empty($this->config['reactions_notifications_emails_enabled']))
		{
			return false;
		}

		return '@steve_reactions/post_reaction';
	}

	public function get_email_template_variables()
	{
		if (empty($this->config['reactions_notifications_emails_enabled']))
		{
			return false;
		}

		return [
			'POST_SUBJECT'		=> htmlspecialchars_decode(censor_text($this->get_data('post_subject'))),
			'REACTION'			=> htmlspecialchars_decode($this->get_data('reaction_type_title')),
			'REACTION_USER'		=> htmlspecialchars_decode($this->user_loader->get_username($this->get_data('user_id'), 'username')),		
			'U_VIEW_POST'		=> generate_board_url() . '/' . 'viewtopic' . $this->php_ext . '?p=' . $this->get_data('post_id') . '#p' . $this->get_data('post_id'),
		];
	}

	public function create_insert_array($data, $pre_create_data = [])
	{
		$this->set_data('post_id', $data['post_id']);
		$this->set_data('poster_id', $data['poster_id']);
		$this->set_data('post_subject', $data['post_subject']);
		$this->set_data('reaction_file_name', $data['reaction_file_name']);
		$this->set_data('reaction_type_title', $data['reaction_type_title']);
		$this->set_data('user_id', $data['user_id']);

		parent::create_insert_array($data, $pre_create_data);
	}
}
