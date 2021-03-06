<?php


/**
 * @author Andreas Kummer, w3concepts AG
 * @copyright Copyright &copy; 2012, w3concepts AG
 */
class Aitsu_File {

	public $mediaid = null;
	public $idart = null;
	public $filename = null;
	public $filesize = 0;
	public $medianame = null;
	public $extension = null;
	public $subline = null;
	public $description = null;
	public $idlang = null;
	public $xtl = 0;
	public $ytl = 0;
	public $xbr = 1;
	public $ybr = 1;
	protected $unsavedChanges = false;

	protected function __construct() {

	}

	public static function factory($mediaid, $idlang) {

		$file = new self();
		$file->mediaid = $mediaid;

		$result = (object) Aitsu_Db :: fetchRow('' .
		'select * from _art_lang where idartlang = ? ', array (
			$idartlang
		));
		$file->idart = $result->idart;
		$file->idlang = $idlang;

		$result = (object) Aitsu_Db :: fetchRow('' .
		'select * from _media where mediaid = ? ', array (
			$mediaid
		));
		$file->filename = $result->filename;
		$file->extension = $result->extension;
		$file->filesize = $result->size;
		$file->uploaded = $result->uploaded;

		$result = (object) Aitsu_Db :: fetchRow('' .
		'select * from _media_description ' .
		'where ' .
		'	mediaid = ? ' .
		'	and idlang = ? ', array (
			$mediaid,
			$file->idlang
		));
		$file->medianame = isset ($result->name) ? $result->name : '';
		$file->subline = isset ($result->subline) ? $result->subline : '';
		$file->description = isset ($result->description) ? $result->description : '';

		return $file;
	}

	public static function upload($idart, $filename, $tmpFilename) {

		$file = new self();
		$file->idart = $idart;
		$file->filename = $filename;
		$file->extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		$file->filesize = filesize($tmpFilename);
		$file->medianame = pathinfo($filename, PATHINFO_BASENAME);

		if (empty ($idart) || $idart == 0) {
			$idart = 'null';
		}

		if (!file_exists(APPLICATION_PATH . '/data/media/' . $idart)) {
			/*
			 * The target directory does not yet exist and therefore needs to be created.
			 */
			mkdir(APPLICATION_PATH . '/data/media/' . $idart, 0777, true);
			chmod(APPLICATION_PATH . '/data/media/' . $idart, 0777);
		}

		$file->_save();

		if ($idart == null) {
			$idart = 0;
		}

		move_uploaded_file($tmpFilename, APPLICATION_PATH . '/data/media/' . $idart . '/' . $file->mediaid . '.' . $file->extension);
	}

	public static function delete($mediaid) {

		Aitsu_Db :: query('' .
		'update _media as media ' .
		'set media.deleted = now() ' .
		'where media.mediaid = :mediaid ', array (
			':mediaid' => $mediaid
		));
	}

	protected function _save() {

		$this->mediaid = Aitsu_Db :: query('' .
		'insert into _media ' .
		'(idart, filename, extension, size, uploaded) ' .
		'values ' .
		'(?, ?, ?, ?, now())', array (
			$this->idart,
			$this->filename,
			$this->extension,
			$this->filesize
		))->getLastInsertId();
	}

	public static function getFiles($idart, $idlang, $pattern = '%', $sort = 'filename', $asc = true, $doCleanUp = false) {

		if (empty ($idart) || $idart == 0) {
			$idart = 'null';
		}

		$pattern = str_replace('*', '%', $pattern);

		$sortCriteria = array (
			'filename' => 'media.filename',
			'medianame' => 'description.name',
			'date' => 'media.uploaded'
		);
		$sort = $sortCriteria[$sort] . ' ' . ($asc ? 'asc' : 'desc');

		$files = Aitsu_Db :: fetchAll('' .
		'select ' .
		'	media.mediaid, ' .
		'	media.idart, ' .
		'	media.filename, ' .
		'	media.extension, ' .
		'	media2.size, ' .
		'	media2.xtl, ' .
		'	media2.ytl, ' .
		'	media2.xbr, ' .
		'	media2.ybr, ' .
		'	media.uploaded, ' .
		'	description.name, ' .
		'	description.subline, ' .
		'	description.description ' .
		'from ( ' .
		'	select ' .
		'		idart, ' .
		'		max(mediaid) as mediaid, ' .
		'		filename, ' .
		'		max(extension) as extension, ' .
		'		max(uploaded) as uploaded ' .
		'	from _media ' .
		'	where deleted is null ' .
		'	group by ' .
		'		filename, ' .
		'		idart ' .
		'	) as media ' .
		'left join _media_description as description on description.mediaid = media.mediaid and description.idlang = ' . $idlang . ' ' .
		'left join _media as media2 on media.mediaid = media2.mediaid ' .
		'where ' .
		'	media.idart ' . ($idart == 'null' ? 'IS NULL ' : '= ' . $idart . '') . ' ' .
		'order by ' .
		'	' . $sort);

		if (!$files) {
			return array ();
		}

		$return = array ();
		foreach ($files as $key => $file) {
			if ($file['idart'] == null) {
				$file['idart'] = 0;
			}

			if ($doCleanUp && !file_exists(APPLICATION_PATH . '/data/media/' . $file['idart'] . '/' . $file['mediaid'] . '.' . $file['extension'])) {
				if (empty ($file['idart']) || $file['idart'] == 0) {
					$file['idart'] = 'null';
				}

				Aitsu_Db :: query('' .
				'delete from _media ' .
				'where ' .
				'	idart = ? ' .
				'	and filename = ? ', array (
					$file['idart'],
					$file['filename']
				));
			} else {
				$return[] = (object) $file;
			}
		}

		return $return;
	}

	public static function getAllFiles($idlang, $sort = 'filename', $asc = true, $doCleanUp = false) {

		$sortCriteria = array (
			'filename' => 'media.filename',
			'medianame' => 'description.name',
			'date' => 'media.uploaded'
		);
		$sort = $sortCriteria[$sort] . ' ' . ($asc ? 'asc' : 'desc');

		$files = Aitsu_Db :: fetchAll('' .
		'select ' .
		'	media.mediaid, ' .
		'	media.idart, ' .
		'	media.filename, ' .
		'	media.extension, ' .
		'	media2.size, ' .
		'	media2.xtl, ' .
		'	media2.ytl, ' .
		'	media2.xbr, ' .
		'	media2.ybr, ' .
		'	media.uploaded, ' .
		'	description.name, ' .
		'	description.subline, ' .
		'	description.description ' .
		'from ( ' .
		'	select ' .
		'		idart, ' .
		'		max(mediaid) as mediaid, ' .
		'		filename, ' .
		'		max(extension) as extension, ' .
		'		max(uploaded) as uploaded ' .
		'	from _media ' .
		'	where deleted is null ' .
		'	group by ' .
		'		filename, ' .
		'		idart ' .
		'	) as media ' .
		'left join _media_description as description on description.mediaid = media.mediaid and description.idlang = ' . $idlang . ' ' .
		'left join _media as media2 on media.mediaid = media2.mediaid ' .
		'order by ' .
		'	' . $sort);

		if (!$files) {
			return array ();
		}

		$return = array ();
		foreach ($files as $key => $file) {
			if (empty ($file['idart']) || $file['idart'] == 0) {
				$file['idart'] = 'null';
			}

			if ($doCleanUp && !file_exists(APPLICATION_PATH . '/data/media/' . $file['idart'] . '/' . $file['mediaid'] . '.' . $file['extension'])) {
				Aitsu_Db :: query('' .
				'delete from _media ' .
				'where ' .
				'	idart = ? ' .
				'	and filename = ? ', array (
					$file['idart'],
					$file['filename']
				));
			} else {
				$return[] = (object) $file;
			}
		}

		return $return;
	}

	public function getImages($idartlang, $pattern = '%', $sort = 'filename', $asc = true) {

		$pattern = str_replace('*', '%', $pattern);

		$sortCriteria = array (
			'filename' => 'media.filename',
			'medianame' => 'description.name',
			'date' => 'media.uploaded'
		);
		$sort = $sortCriteria[$sort] . ' ' . ($asc ? 'asc' : 'desc');

		$files = Aitsu_Db :: fetchAll('' .
		'select ' .
		'	media.mediaid, ' .
		'	media.idart, ' .
		'	media.filename, ' .
		'	media.extension, ' .
		'	media2.size, ' .
		'	date_format(media.uploaded, \'%d.%m.%y, %H:%i\') as uploaded, ' .
		'	description.name, ' .
		'	description.subline, ' .
		'	description.description ' .
		'from ( ' .
		'	select ' .
		'		idart, ' .
		'		max(mediaid) as mediaid, ' .
		'		filename, ' .
		'		max(extension) as extension, ' .
		'		max(uploaded) as uploaded ' .
		'	from _media ' .
		'	where deleted is null ' .
		'	group by ' .
		'		filename, ' .
		'		idart ' .
		'	) as media ' .
		'left join _media as media2 on media.mediaid = media2.mediaid ' .
		'left join _art_lang as artlang on media.idart = artlang.idart ' .
		'left join _media_description as description on description.mediaid = media.mediaid and artlang.idlang = description.idlang ' .
		'where ' .
		'	artlang.idartlang = ? ' .
		'	and (' .
		'		media.filename like ? ' .
		'		or description.name like ? ' .
		'		) ' .
		'	and (' .
		'		media.extension in (\'jpg\', \'jpeg\', \'gif\', \'png\') ' .
		'		) ' .
		'order by ' .
		'	' . $sort, array (
			$idartlang,
			$pattern,
			$pattern
		));

		if (!$files) {
			return array ();
		}

		$return = array ();
		foreach ($files as $key => $file) {
			$return[] = (object) $file;
		}

		return $return;
	}

	public function save() {

		$origFilename = Aitsu_Db :: fetchOne('select filename from _media where mediaid = ?', array (
			$this->mediaid
		));

		if ($origFilename != $this->filename) {
			/*
			 * File name has changed.
			 */
			Aitsu_Db :: query('' .
			'update _media set filename = ? ' .
			'where ' .
			'	idart = ? ' .
			'	and filename = ? ', array (
				trim($this->filename),
				$this->idart,
				$origFilename
			));
		}

		Aitsu_Db :: query('' .
		'update _media set ' .
		'xtl = :xtl, ytl = :ytl, xbr = :xbr, ybr = :ybr ' .
		'where mediaid = :mediaid', array (
			':xtl' => $this->xtl,
			':ytl' => $this->ytl,
			':xbr' => $this->xbr,
			':ybr' => $this->ybr,
			':mediaid' => $this->mediaid
		));

		Aitsu_Db :: query('' .
		'replace into _media_description ' .
		'(mediaid, idlang, name, subline, description) ' .
		'values ' .
		'(?, ?, ?, ?, ?) ', array (
			$this->mediaid,
			$this->idlang,
			$this->medianame,
			$this->subline,
			$this->description
		));

		Aitsu_Util_Dir :: rm(APPLICATION_PATH . '/data/thumbs/' . $this->idart . '/' . $this->mediaid);
	}

	public static function get($path, $inline = false) {

		ob_end_clean();

		$fileSource = APPLICATION_PATH . '/data/media/' . $path;

		if (!file_exists($fileSource)) {
			/*
			 * The filename could have been used instead of the mediaid.
			 */
			if (preg_match('@^(\\d*)/(.*)$@', $path, $match)) {
				$idart = $match[1];
				$filename = $match[2];
				$file = Aitsu_Db :: fetchRow('' .
				'select mediaid, extension from _media ' .
				'where ' .
				'	idart = ? ' .
				'	and filename = ? ', array (
					$idart,
					$filename
				));
				$fileSource = APPLICATION_PATH . '/data/media/' . $idart . '/' . $file['mediaid'] . '.' . strtolower($file['extension']);
				if (!file_exists($fileSource)) {
					header('HTTP/1.0 404 Not Found');
					return;
				}
			} else {
				header('HTTP/1.0 404 Not Found');
				return;
			}
		}

		strtok($path, '/');
		$fileName = strtok("\n");

		if (!$inline) {
			header('Content-Disposition: attachment; filename="' . $fileName . '"');
		}

		$mimeTypes = array (
			'png' => 'image/png',
			'jpg' => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'gif' => 'image/gif',
			'ogg' => 'audio/ogg',
			'mp3' => 'audio/mpeg'
		);

		if (isset ($mimeTypes[$file['extension']])) {
			header('Content-type: ' . $mimeTypes[$file['extension']]);
		} else {
			header('Content-type: application/' . $file['extension']);
		}

		readfile($fileSource);
	}

	public static function getByFilename($ids) {

		if (!is_array($ids) || count($ids) == 0) {
			return array ();
		}

		$ids2 = array ();
		foreach ($ids as $id) {
			if (!empty ($id)) {
				$ids2[] = $id;
			}
		}

		if (count($ids2) == null) {
			return array ();
		}

		$ids = '\'' . implode('\',\'', $ids2) . '\'';

		$files = Aitsu_Db :: fetchAll('' .
		'select ' .
		'	media.mediaid, ' .
		'	media.idart, ' .
		'	media.filename, ' .
		'	media.extension, ' .
		'	media2.size, ' .
		'	date_format(media.uploaded, \'%d.%m.%y, %H:%i\') as uploaded, ' .
		'	description.name, ' .
		'	description.subline, ' .
		'	description.description ' .
		'from ( ' .
		'	select ' .
		'		idart, ' .
		'		max(mediaid) as mediaid, ' .
		'		filename, ' .
		'		max(extension) as extension, ' .
		'		max(uploaded) as uploaded ' .
		'	from _media ' .
		'	where deleted is null ' .
		'	group by ' .
		'		filename, ' .
		'		idart ' .
		'	) as media ' .
		'left join _media as media2 on media.mediaid = media2.mediaid ' .
		'left join _art_lang as artlang on media.idart = artlang.idart ' .
		'left join _media_description as description on description.mediaid = media.mediaid and artlang.idlang = description.idlang ' .
		'where ' .
		'	artlang.idartlang = ? ' .
		'	and media.filename in (' . $ids . ') ' .
		'order by filename asc ', array (
			Aitsu_Registry :: get()->env->idartlang
		));

		if (!$files) {
			return array ();
		}

		$return = array ();
		foreach ($files as $key => $file) {
			$return[] = (object) $file;
		}

		return $return;
	}

	public static function getByMediaId($ids) {

		if (!is_array($ids) || count($ids) == 0) {
			return array ();
		}

		$idlang = Aitsu_Registry :: get()->env->idlang;

		$ids2 = array ();
		foreach ($ids as $id) {
			if (!empty ($id)) {
				$ids2[] = $id;
			}
		}

		if (count($ids2) == null) {
			return array ();
		}

		$ids = implode(',', $ids2);

		$files = Aitsu_Db :: fetchAll('' .
		'select ' .
		'	media2.mediaid, ' .
		'	media2.idart, ' .
		'	media2.filename, ' .
		'	media2.extension, ' .
		'	media3.size, ' .
		'	date_format(media.uploaded, \'%d.%m.%y, %H:%i\') as uploaded, ' .
		'	description.name, ' .
		'	description.subline, ' .
		'	description.description ' .
		'from _media as media ' .
		'left join ( ' .
		'	select ' .
		'		max(mediaid) as mediaid, ' .
		'		idart, ' .
		'		filename, ' .
		'		extension ' .
		'	from _media ' .
		'	group by ' .
		'		idart, ' .
		'		filename, ' .
		'		extension ' .
		'	) as media2 on media.filename = media2.filename and media.idart = media2.idart ' .
		'left join _media as media3 on media2.mediaid = media3.mediaid ' .
		'left join _media_description as description on description.mediaid = media2.mediaid and description.idlang = ? ' .
		'where ' .
		'	media.mediaid in (' . $ids . ') ', array (
			$idlang
		));

		if (!$files) {
			return array ();
		}

		$return = array ();
		foreach ($files as $key => $file) {
			$return[] = (object) $file;
		}

		return $return;
	}

}