<?php
/**
	* Reactions $extends the phpBB Software package.
	* @copyright (c) 2024, Steve, https://steven-clark.tech/
	* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace steve\reactions\acp;

class main_module
{
	protected $phpbb_container;
	protected $request;
	public $page_title;
	public $tpl_name;
	public $u_action;

	public function main($id, $mode)
	{
		global $phpbb_container, $request;

		$this->request = $request;
		$this->tpl_name = 'acp_reactions_body';
		
		$this->phpbb_container = $phpbb_container;
		$acp_controller = $this->phpbb_container->get('steve.reactions.acp_controller');
		$acp_controller->get_acp_data($id, $mode, $this->request->variable('action', ''), $this->u_action);

		switch ($mode)
		{
			case 'settings':
				$this->page_title = 'ACP_REACTIONS_TITLE';
				$acp_controller->reaction_settings();
			break;

			case 'reactions':
				switch ($this->request->variable('action', ''))
				{
					case 'add':
					case 'edit':
						$this->page_title = $this->request->variable('action', '') == 'add' ? 'ACP_REACTION_ADD' : 'ACP_REACTION_EDIT';
						$acp_controller->edit_add();
						return;
					break;
					case 'delete_data':
						$acp_controller->delete_data();
					break;
					case 'delete_reaction_type':
						$this->tpl_name = 'acp_reactions_type_delete';
						$acp_controller->delete_reaction_type();
					break;
					case 'resync_refresh_confirm':
					case 'resync_refresh':
						$acp_controller->resync_refresh();
					break;
					case 'move_up':
					case 'move_down':
						$acp_controller->move_up_down();
					break;
					case 'activate':
					case 'deactivate':
						$acp_controller->activate_deactivate();
					break;
				}

				$this->page_title = 'ACP_REACTION_TYPES';
				$acp_controller->sort_reaction_order();
				$acp_controller->acp_reactions_main();

			break;
			default;
		}
	}
}
