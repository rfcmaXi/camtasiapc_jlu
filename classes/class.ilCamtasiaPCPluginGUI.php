<?php
/**
 * University of Giessen Camtasia Editor-Block Plugin for ILIAS
 *
 * @author Martin Gorgas <martin.gorgas@hrz.uni-giessen.de>
 **/
require_once('Services/Form/classes/class.ilPropertyFormGUI.php');
require_once('Services/COPage/classes/class.ilPageComponentPluginGUI.php');
require_once('Services/Repository/classes/class.ilRepositorySelectorExplorerGUI.php');

/**
 * Class: ilCamtasiaPCPluginGUI
 *  This is the GUI class for managing the PageCompontent behaviour,
 *  like creating new entry or editing an existing one.
 *
 * @ilCtrl_isCalledBy ilCamtasiaPCPluginGUI: ilPCPluggedGUI
 */
class ilCamtasiaPCPluginGUI extends ilPageComponentPluginGUI {
	// List of visible types that can contain camtasia object(s)
  const VISIBILE_TYPES  = array('root', 'cat', 'grp', 'fold', 'crs', 'xcam');
  // Selectable types which are camtasia objects
  const CLICKABLE_TYPES = array('xcam');

	/**
	 * Function: getElementHTML($mode, $properties, $pluginVersion)
	 *  Used to display/render the actual block element.
   *
   * @param $mode <String> Display mode (eg. edit or presentation)
   * @param $properties <Array> List of configured properties
   * @param $pluginVersion <String> Version of plugin
   *
   * @return <String> HTML code to display
	 */
	public function getElementHTML(string $mode, array $properties, string $pluginVersion): string {
		global $ilUser;

		// Fetch properties
		$refId = $properties['camtasia_id'];
		$width = isset($properties['width']) ? intval($properties['width']) : 320;
		$height = isset($properties['height']) ? intval($properties['height']) : 240;

		// Fetch object with given ref-id
		$obj = ilObjectFactory::getInstanceByRefId($refId, false);

		// Object found, fill template
		if ($obj && $obj->getType() === 'xcam' && ($player = $obj->getFullscreenPlayer())) {
			// Only show the name in no-media mode
			if ($ilUser->getPref('ilPageEditor_MediaMode') === 'disable') {
				return "<div style=\"text-align: center;\">{$this->plugin->txt('MEDIA')}: {$obj->getTitle()}</div>";
			}

			// Load template
			$tpl = $this->plugin->getTemplate('tpl.content.html', false, false);

			// Add link and size via variables or existing css
			$tpl->setVariable('LINK', $player);
			if (!$width || !$height) {
				//$tpl->addCss($obj->getEmbedCSS());
				$GLOBALS['tpl']->addCss($obj->getEmbedCSS());
			} else {
				$tpl->setVariable('CSS_SIZE', "width: {$width}px; height: {$height}px;");

				// Return template HTML
				return $tpl->get();
			}
		}
		// Object was not found, show information
		else {
			$nope = sprintf($this->plugin->txt('NOPE'), $refId);
			return "<div style=\"text-align: center;\">{$nope}</div>";
		}
	}

	/**
	 * Function: executeCommand()
	 *  This method gets called by ilUIPluginRouterGUI (actually ilCtrl) when being
	 *  displayed using ilCtrl (and routed through ilUIPluginRouterGUI as BaseClass).
	 *  It renders the ILIAS standard template and leaves the rest (filling it) to others.
	 */
	public function executeCommand():void {
		global $ilCtrl;

		// Just perform the command
		$this->performCommand($ilCtrl->getCmd('edit'));
	}

	/**
	 * Function: performCommand($cmd)
	 *  This methods gets called by executeCommand() with the ilCtrl command
	 *  given as parameter. It is responsible for selecting the correct method
	 *  (based on $command) that should be used to add content to the template.
	 *
	 * @param $cmd <String> Command that should be executed
	 *  Supported commands: showForm, showData
	 */
	protected function performCommand($cmd) {
		switch ($cmd) {
			// Allowed commands
			case 'insert':
			case 'create':
			case 'edit':
			case 'editObject':
			case 'editSettings':
			case 'updateObject':
			case 'updateSettings':
			case 'cancel':
				$this->$cmd();
				break;
			// Fallback
			default:
				$this->edit();
				break;
		}
	}

	/**
	 * Function: insert()
	 *  This method gets called when rendering this GUI class with command 'insert',
	 *  eg. when inserting a new block-element.
	 */
	public function insert(): void {
		global $tpl;

		// Fetch editing form and add to main-template
		$form = $this->initCreateForm();
		if (!$form->handleCommand()) {
			$tpl->setContent($form->getHTML());
		}
	}

    /**
     * Edit an object.
     */
    public function edit(): void { 
        $this->editObject(); 
    }

    /**
     * Edit the object.
     */
    public function editObject(): void {
		global $tpl;

		// Add tabs and activate editing tab
		$this->setTabs('editObject');

		// Fetch editing form and add to main-template
		$prop = $this->getProperties();
		$form = $this->initEditObjectForm($prop['camtasia_id']);
		if (!$form->handleCommand()) {
			$tpl->setContent($form->getHTML());
		}
	}

	/**
	 * Function: editSettings()
	 *  This method gets called when rendering this GUI class with command 'editSettings',
	 *  eg. when editing an existing block-element settings. (Tab: editSettings)
	 */
	public function editSettings() {
		global $tpl;

		// Add tabs and activate editing tab
		$this->setTabs('editSettings');

		// Fetch editing form and add to main-template
		$prop = $this->getProperties();
		$width = isset($prop['width']) ? $prop['width'] : 320;
		$height = isset($prop['height']) ? $prop['height'] : 240;

		$form = $this->initEditSettingsForm($width, $height);
		$tpl->setContent($form->getHTML());
	}

	/**
	 * Function: create()
	 *  This method gets called when rendering the GUI class with command 'create',
	 *  eg. when processing the 'insert' form to actually create the new block-element.
	 */
     public function create(): void {
         global $tpl, $lng;

         // Validate and create element
         $camtasiaId = $_REQUEST['camtasia_id'];
         if (isset($camtasiaId)) {
             // Build properties from form values and default
             $prop = $this->getProperties();
             $prop['camtasia_id'] = $camtasiaId;

             // Create new element from given properties
             if ($this->createElement($prop)) {
                 $tpl->setOnScreenMessage('success',$lng->txt('msg_obj_modified'), true);
                 $this->returnToParent();
             }
         }

         // Render form if not returned to parent
         $form = $this->initCreateForm($camtasiaId);
         $tpl->setContent($form->getHtml());
     }

	/**
	 * Function: updateObject()
	 *  This method gets called when rendering the GUI class with command 'updateObject',
	 *  eg. when processing the 'editObject' form to update the current block-element.
	 */
	public function updateObject() {
		global $tpl, $lng;

		// Validate and create element
		$camtasiaId = $_REQUEST['camtasia_id'];
		if (isset($camtasiaId)) {
			// Build properties from form values and default
			$prop = $this->getProperties();
			$prop['camtasia_id'] = $camtasiaId;

			// Update existing block-element with given properties
			if ($this->updateElement($prop)) {
				$tpl->setOnScreenMessage('success',$lng->txt('msg_obj_modified'), true);
				$this->returnToParent();
			}
		}
		// Render form if not returned to parent
		$form = $this->initEditObjectForm($camtasiaId);
		$tpl->setContent($form->getHtml());
	}

	/**
	 * Function: updateSettings()
   *  This method gets called when rendering the GUI class with command 'updateSettings',
   *  eg. when processing the 'editSettings' form to update the current block-element.
	 */
	public function updateSettings() {
		global $tpl, $lng;

		// Fetch editing form and validate and fill form from $_POST values and add to it main-template
		$form = $this->initEditSettingsForm();
		if ($form->checkInput()) {
		// Build properties from form values and default
		$prop = $this->getProperties();
		$prop['width']  = $form->getInput('width');
		$prop['height'] = $form->getInput('height');

		// Update existing block-element with given properties
		if ($this->updateElement($prop)) {
			$tpl->setOnScreenMessage('success',$lng->txt('msg_obj_modified'), true);
			$this->returnToParent();
		}
	}

	// Render form if not returned to parent
	$form->setValuesByPost();
		$tpl->setContent($form->getHtml());
	}

	/**
	 * Function: cancel()
   *  This method gets called when rendering this GUI class with command 'cancel'.
	 */
	public function cancel() {
		$this->returnToParent();
	}

	/**
	 * Function: initCreateForm()
	 *  Creates and returns the property form object for creating
	 *  a new camtasia block-element.
	 *
	 * @return <ilRepositorySelectorExplorerGUI> Form for configuring new block-element camtasia object
	 */
	protected function initCreateForm() {
		// Create a simple Repository-Explorer
		$explorer = new ilRepositorySelectorExplorerGUI($this, 'insert', $this, 'create', 'camtasia_id');
		$explorer->setTypeWhiteList(self::VISIBILE_TYPES);
		$explorer->setClickableTypes(self::CLICKABLE_TYPES);

		// Return form
		return $explorer;
	}

	/**
	 * Function: initEditObjectForm($parentId)
	 *  Creates and returns the property form object for editing an
   *  existing block-element camtasia object.
   *
   * @param $parentId <Number> Current camtasia object to pre-select/highlight.
	 *
	 * @return <ilRepositorySelectorExplorerGUI> Form for configuring existing block-element camtasia object
	 */
	protected function initEditObjectForm($parentId = null) {
		// Create a simple Repository-Explorer
		$explorer = new ilRepositorySelectorExplorerGUI($this, 'editObject', $this, 'updateObject', 'camtasia_id');
		$explorer->setTypeWhiteList(self::VISIBILE_TYPES);
		$explorer->setClickableTypes(self::CLICKABLE_TYPES);

		// Set node/highlight to given object
		if ($parentId) {
			$explorer->setPathOpen($parentId);
			$explorer->setHighlightedNode($parentId);
		}

		// Return form
		return $explorer;
	}

	/**
	 * Function: initEditSettingsForm($width, $height)
	 *  Creates and returns the property form object for editing an
	 *  existing block-element settings such as display width and height.
	 *
	 * @param $width <Number> Desired display-width of block-element video
	 * @param $height <Number> Desired display-height of block-element video
	 *
	 * @return <ilPropertyFormGUI> Form for configuring existing block-element width and height
	 */
	protected function initEditSettingsForm($width = null, $height = null) {
		global $ilCtrl, $lng;

		// Create and configure new property form
		$form = new ilPropertyFormGUI();
		$form->setTitle($this->plugin->txt('EDIT::SETTINGS::TITLE'));
		$form->setFormAction($ilCtrl->getFormAction($this, 'updateSettings'));
		$form->addCommandButton('updateSettings', $lng->txt('save'));

		// Add input elements for width & height
		$widthInput = new ilNumberInputGUI($this->plugin->txt('EDIT::SETTINGS::WIDTH'), 'width');
		$form->addItem($widthInput);
		$heightInput = new ilNumberInputGUI($this->plugin->txt('EDIT::SETTINGS::HEIGHT'), 'height');
		$form->addItem($heightInput);

		// Initialize form inputs if values are given
		if ($width) {
			$widthInput->setValue($width);
		}
		if ($height) {
			$heightInput->setValue($height);
		}
		// Return form
		return $form;
	}

	/**
	 * Function: setTabs($active)
	 *  Called by various command functions to add (and activate) tabs.
	 *
	 * @param $active <String> The currently active tab (matches first parameter)
	 */
	protected function setTabs($active) {
		global $ilTabs, $ilCtrl;

		// Add one tab for editing the settings
		$ilTabs->addTab('editObject',   $this->plugin->txt('TAB::EDIT::OBJECT'),   $ilCtrl->getLinkTarget($this, 'editObject'));
		$ilTabs->addTab('editSettings', $this->plugin->txt('TAB::EDIT::SETTINGS'), $ilCtrl->getLinkTarget($this, 'editSettings'));
		$ilTabs->activateTab($active);
	}
}