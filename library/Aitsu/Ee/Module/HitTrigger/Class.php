<?php


/**
 * @author Andreas Kummer, w3concepts AG
 * @copyright Copyright &copy; 2010, w3concepts AG
 * 
 * {@id $Id: Class.php 17166 2010-06-17 08:56:31Z akm $}
 */

class Aitsu_Ee_Module_HitTrigger_Class extends Aitsu_Ee_Module_Abstract {
	
	public static function about() {

		return (object) array (
			'name' => 'HitTrigger',
			'description' => Aitsu_Translate :: translate('Returns am img tag triggering the hit counter and counts the hits on subsequent request.'),
			'type' => 'Statistics',
			'author' => (object) array (
				'name' => 'Andreas Kummer',
				'copyright' => 'w3concepts AG'
			),
			'version' => '1.0.0',
			'status' => 'stable',
			'url' => null,
			'id' => '4cc1cf22-e768-4880-aa7d-40ab7f000101'
		);
	}

	public static function init($context) {
		
		Aitsu_Content_Edit :: noEdit('HitTrigger', true);

		if (isset($_REQUEST['renderOnly'])) {
			/*
			 * Disable caching.
			 */
			Aitsu_Registry :: get()->config->cache->page->enable = false;
			
			/*
			 * Count the current hit.
			 */
			Aitsu_Persistence_Hit :: hit();
			
			/*
			 * Return an empty gif.
			 */
			Aitsu_Util :: endAndCleanOutputBuffering();
			header("Content-type: image/gif");
			header("Cache-Control: no-cache, must-revalidate");
			header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
			readfile(dirname(__FILE__) . '/0.gif');
			exit(0);
		}
		
		if (Aitsu_Registry :: isEdit()) {
			return '';
		}
		
		return '<img src="{ref:idart-' . Aitsu_Registry :: get()->env->idart . '}?renderOnly=HitTrigger" />';
	}
}