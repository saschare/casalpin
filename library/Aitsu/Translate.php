<?php


/**
 * aitsu translation class.
 * 
 * @author Andreas Kummer, w3concepts AG
 * @copyright Copyright &copy; 2010, w3concepts AG
 * 
 * {@id $Id: Translate.php 18800 2010-09-16 21:03:42Z akm $}
 */

class Aitsu_Translate {

	protected $translationMap = array ();
	protected $idlang = null;

	protected function __construct() {

		$this->idlang = Aitsu_Registry :: get()->env->idlang;

		$this->_readTranslationData();
	}

	protected function _getInstance() {

		static $instance;

		if (!isset ($instance)) {
			$instance = new self();
		}

		return $instance;
	}

	public static function _($text) {

		$instance = self :: _getInstance();

		if (!isset($instance->translationMap[$text]) || strlen($instance->translationMap[$text]) == 0) {
			return $text;
		}

		return $instance->translationMap[$text];
	}

	protected function _readTranslationData() {

		$results = Aitsu_Db :: fetchAll('' .
		'select tkey, tvalue from _translate ' .
		'where idlang = ? ', array (
			$this->idlang
		));

		if (!$results) {
			return;
		}

		foreach ($results as $result) {
			$this->translationMap[$result['tkey']] = $result['tvalue'];
		}
	}

	public static function populate($idlang) {

		Aitsu_Db :: query('update _translate set obsolete = now() where obsolete is null and idlang = ? ', array (
			$idlang
		));

		self :: _populateFromClasses($idlang);
	}

	protected static function _populateFromClasses($idlang) {

		$files = scandir(APPLICATION_LIBPATH);

		$files = self :: _scanDir(APPLICATION_LIBPATH . '/Aitsu', array (
			'php',
			'phtml'
		));
		$files = array_merge($files, self :: _scanDir(APPLICATION_LIBPATH . '/Comm', array (
			'php',
			'phtml'
		)));
		$files = array_merge($files, self :: _scanDir(APPLICATION_LIBPATH . '/Local', array (
			'php',
			'phtml'
		)));
		$files = array_merge($files, self :: _scanDir(APPLICATION_PATH . '/plugins', array (
			'php',
			'phtml'
		)));
		$files = array_merge($files, self :: _scanDir(APPLICATION_PATH . '/skins', array (
			'php',
			'phtml'
		)));

		foreach ($files as $file) {
			$content = file_get_contents($file);
			if (preg_match_all("@Aitsu_Translate\\s*\\:{2}\\s*_\\((['\"])(.*?)(?:\\1\\))@", $content, $matches) > 0) {
				foreach ($matches[2] as $key) {
					$key = trim($key);
					if (Aitsu_Db :: fetchOne('' .
						'select count(*) from _translate where tkey = ? and idlang = ? ', array (
							$key,
							$idlang
						)) == 0) {
						Aitsu_Db :: query('' .
						'insert into _translate ' .
						'(idlang, tkey) ' .
						'values ' .
						'(?, ?) ', array (
							$idlang,
							$key
						));
					} else {
						Aitsu_Db :: query('update _translate set obsolete = null where tkey = ? and idlang = ? ', array (
							$key,
							$idlang
						));
					}
				}
			}
		}
	}

	protected static function _scanDir($dir, $extensions) {

		$return = array ();
		$files = scandir($dir);

		foreach ($files as $file) {
			$pathInfo = pathinfo($file);
			$extension = isset($pathInfo['extension']) ? $pathInfo['extension'] : '';
			if ($file != '.' && $file != '..' && in_array($extension, $extensions)) {
				$return[] = $dir . '/' . $file;
			}
			if ($file != '.' && $file != '..' && is_dir($dir . '/' . $file)) {
				$return = array_merge($return, self :: _scanDir($dir . '/' . $file, $extensions));
			}
		}

		return $return;
	}

	public static function getTranslationData($idlang, $pattern = '*') {

		$pattern = str_replace('*', '%', $pattern);

		$return = Aitsu_Db :: fetchAll('' .
		'select * from _translate ' .
		'where ' .
		'	idlang = ? ' .
		'	and ( ' .
		'		tkey like ? ' .
		'		or tvalue like ? ' .
		'		) ' .
		'	and obsolete is null ' .
		'order by tkey asc ', array (
			$idlang,
			$pattern,
			$pattern
		));

		if (!$return) {
			return array ();
		}

		return $return;
	}
	
	public static function translate($text) {
		
		if (!is_a(Aitsu_Registry :: get()->Zend_Translate, 'Zend_Translate')) {
			return $text;
		}
		
		return Aitsu_Registry :: get()->Zend_Translate->translate($text);
	}
}