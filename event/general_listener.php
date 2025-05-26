<?php
/**
	* Reactions $extends the phpBB Software package.
	* @copyright (c) 2024, Steve, https://steven-clark.tech/
	* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace steve\reactions\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class general_listener implements EventSubscriberInterface
{
	protected $auth;
	protected $config;
	protected $helper;
	protected $language;
	protected $template;
	protected $user;
	protected $php_ext;
	protected $user_enable_reactions;

	public function __construct(
		\phpbb\auth\auth $auth,
		\phpbb\config\config $config,
		\phpbb\controller\helper $helper,
		\phpbb\language\language $language,
		\phpbb\template\template $template,
		\phpbb\user $user,
		$php_ext)
	{
		$this->auth = $auth;
		$this->config = $config;
		$this->helper = $helper;
		$this->language = $language;
		$this->template = $template;
		$this->user = $user;
		$this->php_ext = $php_ext;
		$this->user_enable_reactions = $user_enable_reactions = !$this->auth->acl_get('u_disable_reactions') ? true : ($this->user->data['user_enable_reactions'] ? true  : false);
	}

	static public function getSubscribedEvents()
	{
		return [
			'core.user_setup'						=> 'load_language_on_setup',
			'core.page_header'						=> 'add_page_header_link',
			'core.viewonline_overwrite_location'	=> 'viewonline_page',
		];
	}

	public function load_language_on_setup($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = [
			'ext_name' => 'steve/reactions',
			'lang_set' => 'common',
		];
		$event['lang_set_ext'] = $lang_set_ext;
	}

	public function add_page_header_link()
	{
		$reactions_enabled = !empty($this->config['reactions_enabled'] && !empty($this->config['reactions_page_enabled'])) ? true : false;
		$this->template->assign_vars([
			'REACTIONS_ENABLED' 		=> !empty($this->config['reactions_enabled']) ? true : false,
			'REACTIONS_BUTTON_ICON'		=> isset($this->config['reactions_button_icon']) ? $this->config['reactions_button_icon'] : '',
			'U_VIEW_REACTIONS'			=> ($this->auth->acl_get('u_view_reactions_pages') && $reactions_enabled && $this->user_enable_reactions) ? $this->helper->route('steve_reactions_view_reactions_controller_page') : false,
		]);
	}

	public function viewonline_page($event)
	{
		if (empty($this->config['reactions_enabled']) && empty($this->config['reactions_page_enabled']) || !$this->auth->acl_get('u_view_reactions_pages') || !$this->user_enable_reactions)
		{
			return;
		}

		if ($event['on_page'][1] === 'app' && strrpos($event['row']['session_page'], 'app.' . $this->php_ext . '/' . 'reactions') === 0)
		{
			$event['location'] = $this->language->lang('VIEWING_REACTIONS');
			$event['location_url'] = $this->helper->route('steve_reactions_view_reactions_controller_page');
		}
	}
}
