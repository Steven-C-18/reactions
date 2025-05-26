<?php
/**
	* Reactions $extends the phpBB Software package.
	* @copyright (c) 2024, Steve, https://steven-clark.tech/
	* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace steve\reactions\ucp;

class main_module
{
	protected $phpbb_container;

	public $page_title;
	public $tpl_name;
	public $u_action;
	
	function main($id, $mode)
	{
		global $phpbb_container;
		$this->phpbb_container = $phpbb_container;

		$this->language = $this->phpbb_container->get('language');
		$this->tpl_name = 'ucp_reactions_body';
		$this->page_title = $this->language->lang('UCP_REACTIONS_TITLE');

		$ucp_controller = $this->phpbb_container->get('steve.reactions.ucp_controller')->ucp_reaction_settings($this->u_action);
	}
}
