<?php
/**
 * University of Giessen Camtasia Editor-Block Plugin for ILIAS
 *
 * @author Martin Gorgas <martin.gorgas@hrz.uni-giessen.de>
 **/
require_once('Services/COPage/classes/class.ilPageComponentPlugin.php');


/**
 * Class: ilCamtasiaPCPlugin
 *  This class registers the Camtasia PageComponent plugin with ILIAS.
 */
class ilCamtasiaPCPlugin extends ilPageComponentPlugin {
	/**
   * Function: getPluginName()
   *  Returns the name of the Plugin. This must match with
   *  the directory-name of the plugin or else ILIAS fails
   *  to parse its configuration...
   *
   * @return <String> Name of plugin, matching its directory-name
   */
	function getPluginName() {
		return 'CamtasiaPC';
	}


	/**
	 * Function: isValidParentType($parentType)
   *  This is called to check for which object-types this PageComponent
	 *  is allowed. We allow all types.
	 *
	 * @param $parentType <String> Object type to check PageComponent permission for
	 *
	 * @return <Boolean> True if PC is allowed for given type
	 */
	function isValidParentType($parentType) {
		return true;
	}
}
