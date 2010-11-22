<?php


/**
 * aitsu backend bootstrap
 * @version $Id: Bootstrap.php 18797 2010-09-16 20:59:45Z akm $
 * @author Andreas Kummer, w3concepts AG
 * @copyright Copyright &copy; 2010, w3concepts AG
 */

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap {

	protected function _initDisableMagicQuotes() {

		if (get_magic_quotes_gpc()) {
			$process = array (
				& $_GET,
				& $_POST,
				& $_COOKIE,
				& $_REQUEST
			);
			while (list ($key, $val) = each($process)) {
				foreach ($val as $k => $v) {
					unset ($process[$key][$k]);
					if (is_array($v)) {
						$process[$key][stripslashes($k)] = $v;
						$process[] = & $process[$key][stripslashes($k)];
					} else {
						$process[$key][stripslashes($k)] = stripslashes($v);
					}
				}
			}
			unset ($process);
		}
	}

	protected function _initDoctype() {

		$this->bootstrap('view');
		$view = $this->getResource('view');
		$view->doctype('XHTML1_STRICT');
	}

	protected function _initAutoloaders() {

		$autoloader = Zend_Loader_Autoloader :: getInstance();
		$autoloader->registerNamespace('Aitsu_');
		$autoloader->registerNamespace('Comm_');
		$autoloader->registerNamespace('Local_');
	}

	protected function _initSetLogging() {

		set_error_handler(array (
			'Aitsu_Core_Logger',
			'errorHandler'
		), E_ALL /* ^ E_NOTICE*/
		);
	}

	protected function _initBackendConfig() {

		Aitsu_Registry :: get()->config = Aitsu_Config_Ini :: getInstance('backend');
	}

	protected function _initDatabase() {

		Aitsu_Registry :: get()->db = Zend_Db :: factory(Aitsu_Registry :: get()->config->database);
	}

	protected function _initSession() {

		if (!isset ($_COOKIE['PHPSESSID']) && isset ($_POST['PHPSESSID'])) {
			$_GET['PHPSESSID'] = $_POST['PHPSESSID'];
		}

		Aitsu_Db :: query('' .
		'create table if not exists _aitsu_session (' .
		'	id char(32), ' .
		'	modified int, ' .
		'	lifetime int, ' .
		'	data longtext, ' .
		'	primary key (id)' .
		')');

		Zend_Db_Table_Abstract :: setDefaultAdapter(Aitsu_Db :: getDb());
		Zend_Session :: setOptions(array (
			'use_only_cookies' => 'off',
			'use_cookies' => 'on'
		));
		Zend_Session :: setSaveHandler(new Zend_Session_SaveHandler_DbTable(array (
			'name' => Aitsu_Db :: prefix('_aitsu_session'),
			'primary' => 'id',
			'modifiedColumn' => 'modified',
			'dataColumn' => 'data',
			'lifetimeColumn' => 'lifetime'
		)));
		Zend_Session :: start(array (
			'name' => 'AITSU'
		));

		Aitsu_Registry :: get()->session = new Zend_Session_Namespace('aitsu');
	}

	protected function _initLocale() {

		$session = Aitsu_Registry :: get()->session;

		if ($session->belang == null) {
			$session->belang = 'de_DE';
		}

		$availableLangs = array (
			'en',
			'de'
		);

		$lang = substr($session->belang, 0, 2);

		$lang = in_array($lang, $availableLangs) ? $lang : 'en';

		$adapter = new Zend_Translate('gettext', APPLICATION_PATH . '/languages/' . $lang . '/translate.mo', $lang);
		Aitsu_Registry :: get()->Zend_Translate = $adapter;
		Zend_Registry :: set('Zend_Translate', $adapter);

		Aitsu_Registry :: get()->env->locale = new Zend_Locale($lang);
	}

	protected function _initRegisterPlugins() {

		if (isset (Aitsu_Registry :: get()->config->setup->password) && substr($_SERVER['REQUEST_URI'], -1 * strlen('/setup/' . Aitsu_Registry :: get()->config->setup->password)) == '/setup/' . Aitsu_Registry :: get()->config->setup->password) {
			/*
			 * Setup has to be made. Plugin registration is skipped and
			 * the user is redirected to the script controller.
			 */

			Aitsu_Registry :: get()->allowTempAccess = true;
			Zend_Controller_Front :: getInstance()->registerPlugin(new Aitsu_Adm_Controller_Plugin_Installation());
			return;
		}

		Zend_Controller_Front :: getInstance()->registerPlugin(new Aitsu_Adm_Controller_Plugin_Accesscontrol());
		Zend_Controller_Front :: getInstance()->registerPlugin(new Aitsu_Adm_Controller_Plugin_Clientlang());
		Zend_Controller_Front :: getInstance()->registerPlugin(new Aitsu_Adm_Controller_Plugin_Navigation());
		Zend_Controller_Front :: getInstance()->registerPlugin(new Aitsu_Adm_Controller_Plugin_Listeners());
	}

	protected function _initRouter() {

		Zend_Controller_Front :: getInstance()->getRouter()->addRoute('plugin', new Zend_Controller_Router_Route('plugin/:area/:plugin/:paction/*', array (
			'controller' => 'plugin',
			'action' => 'index',
			'area' => 'none',
			'plugin' => 'none',
			'paction' => 'index'
		)));

		Zend_Controller_Front :: getInstance()->getRouter()->addRoute('aplugin', new Zend_Controller_Router_Route('aplugin/:plugin/:paction/*', array (
			'controller' => 'plugin',
			'action' => 'article',
			'plugin' => 'none',
			'paction' => 'index'
		)));

		Zend_Controller_Front :: getInstance()->getRouter()->addRoute('dashboard', new Zend_Controller_Router_Route('dashboard/:plugin/:paction/*', array (
			'controller' => 'plugin',
			'action' => 'dashboard',
			'plugin' => 'none',
			'paction' => 'index'
		)));

		Zend_Controller_Front :: getInstance()->getRouter()->addRoute('cplugin', new Zend_Controller_Router_Route('cplugin/:plugin/:paction/*', array (
			'controller' => 'plugin',
			'action' => 'category',
			'plugin' => 'none',
			'paction' => 'index'
		)));

		Zend_Controller_Front :: getInstance()->getRouter()->addRoute('plugins', new Zend_Controller_Router_Route('plugins/:area/', array (
			'controller' => 'plugins',
			'action' => 'index',
			'area' => 'none'
		)));
	}
	
	protected function _initAppStatus() {
		
		Aitsu_Application_Status :: isEdit(true);
		Aitsu_Application_Status :: isPreview(true);
		Aitsu_Application_Status :: setEnv('backend');
		Aitsu_Application_Status :: lock();
	}
}
?>
