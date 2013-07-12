<?php


/**
 * @author Frank Ammari, meine experten GbR
 * @copyright Copyright &copy; 2010, meine experten GbR
 */

class Skin_Module_ArticleNavigation_Class extends Aitsu_Module_Abstract {

	public static function init($context) {

		$index = $context['index'];

		$instance = new self();
		$view = $instance->_getView();

		$params = Aitsu_Util :: parseSimpleIni($context['params']);

		$template = isset ($params->template) ? $params->template : 'index';


		$idcat = $params->idcat ? $params->idcat : Aitsu_Registry :: get()->env->idcat;
		$show = 2;
		
		$idcat = explode(',',$idcat);

		$it = Aitsu_Core_Article_Aggregation :: factory()
    		->useOfStartArticle($show) // show only startarticles, 1 = show all, 2 = do not show start articles
    		->whereInCategories($idcat) // idcats of the categories in question
    		->orderBy('artsort', true) // order ascending, false = descending
    		//->addFilter("? != 'no'", 'showInArticleList')
    		->fetch(); // optional an offset and a limit can be specified

		if (count($it->count())==0) {
		    if (Aitsu_Registry :: get()->env->edit == '1') {
	        	return '<div>' . $index . ' // ' . Aitsu_Translate :: _('No articles available for category id: ') . $idcat[0] . '</div>';
    		} else {
   				return '';
   			}
		}

		$view->article = $it;
		$view->idart = Aitsu_Registry :: get()->env->idart;

		//$output = $view->render('index.phtml');
		$output = $view->render($template . '.phtml');

		return $output;
	}
}