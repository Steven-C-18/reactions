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
	protected $template;

	public $page_title;
	public $tpl_name;
	public $u_action;

	public function main($id, $mode)
	{
		global $phpbb_container, $request, $template;

		$this->phpbb_container = $phpbb_container;
		$this->request = $request;
		$this->template = $template;

		$this->tpl_name = 'acp_reactions_body';

		$action = $this->request->variable('action', '');
		$submit = $this->request->is_set_post('submit');

		$acp_controller = $this->phpbb_container->get('steve.reactions.acp_controller');

		add_form_key('reactions');
		if ($submit && !check_form_key('reactions'))
		{
			trigger_error('FORM_INVALID' . adm_back_link($this->u_action), E_USER_WARNING);
		}

		$acp_controller->get_acp_data($id, $mode, $action, $submit, $this->u_action);

		switch ($mode)
		{
			case 'settings':
				$this->page_title = 'ACP_REACTIONS_TITLE';
				$acp_controller->reaction_settings($submit, $this->u_action);
			break;

			case 'reactions':
				switch ($action)
				{
					case 'add':
					case 'edit':
						$this->page_title = ($action == 'add') ? 'ACP_REACTION_ADD' : 'ACP_REACTION_EDIT';
						$acp_controller->edit_add();

						return;
					break;

					case 'delete_data'://comfirm
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

					case 'resync_refresh_confirm_light':
					case 'resync_refresh_light':
						$acp_controller->resync_refresh_light();
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
