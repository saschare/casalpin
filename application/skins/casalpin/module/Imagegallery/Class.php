<?php

/**
 * @author Christian Kehres <c.kehres@webtischlerei.de>
 * @copyright (c) 2013, webtischlerei <http://www.webtischlerei.de>
 */
class Skin_Module_Imagegallery_Class extends Moraso_Module_Abstract {

    protected function _getDefaults() {

        $defaults = array(
            'width' =>200,
            'height' => 200,
            'render' => 0,
            'template' => 'index',
            'idart' => Aitsu_Registry::get()->env->idart,
            'attr' => '',
            'all' => false,
            'customFields' => 2,
            'style' => '',
            'float' => ''
        );

        return $defaults;
    }

    protected function _main() {

        $defaults = $this->_moduleConfigDefaults;

        $translation = array();
        $translation['configuration'] = Aitsu_Translate::_('Configuration');

        if ($defaults['configurable']['idart']) {
            $idart = Aitsu_Content_Config_Text::set($this->_index, 'idart', Aitsu_Translate::_('Article (idart)'), $translation['configuration']);
        }

        $idart = !empty($idart) ? (int) $idart : $defaults['idart'];

        if ($defaults['configurable']['width']) {
            if (!isset($defaults['selects']['width'])) {
                $width = Aitsu_Content_Config_Text::set($this->_index, 'width', Aitsu_Translate::_('Width'), $translation['configuration']);
            } else {
                $width_select = Aitsu_Content_Config_Select::set($this->_index, 'width', Aitsu_Translate::_('Width'), array_flip($defaults['selects']['width']['names']), $translation['configuration']);
                $width = $defaults['selects']['width']['values'][$width_select];
            }
        }

        $width = !empty($width) ? (int) $width : $defaults['width'];

        if ($defaults['configurable']['height']) {
            if (!isset($defaults['selects']['height'])) {
                $height = Aitsu_Content_Config_Text::set($this->_index, 'height', Aitsu_Translate::_('Height'), $translation['configuration']);
            } else {
                $height_select = Aitsu_Content_Config_Select::set($this->_index, 'height', Aitsu_Translate::_('Height'), array_flip($defaults['selects']['height']['names']), $translation['configuration']);
                $height = $defaults['selects']['height']['values'][$height_select];
            }
        }

        $height = !empty($height) ? (int) $height : $defaults['height'];

        if ($defaults['configurable']['render']) {
            $renderSelect = array(
                'skalieren' => 0,
                'zuschneiden' => 1,
                'fokussieren' => 2
            );

            $render = Aitsu_Content_Config_Select::set($this->_index, 'render', Aitsu_Translate::_('Render'), $renderSelect, $translation['configuration']);
        }

        $render = isset($render) && strlen($render) > 0 ? (int) $render : $defaults['render'];

        if ($defaults['configurable']['template']) {
            $template = Aitsu_Content_Config_Select::set($this->_index, 'template', Aitsu_Translate::_('Template'), $this->_getTemplates(), $translation['configuration']);
        }

        $template = !empty($template) ? $template : $defaults['template'];

        if ($defaults['configurable']['all']) {
            $showAllSelect = array(
                'show all Images' => true,
                'select single Images' => false
            );

            $all = Aitsu_Content_Config_Radio::set($this->_index, 'all', Aitsu_Translate::_('show all Images'), $showAllSelect, $translation['configuration']);
        }

        $all = isset($all) && strlen($all) > 0 ? filter_var($all, FILTER_VALIDATE_BOOLEAN) : $defaults['all'];

        if (!$all) {
            $images = Moraso_Content_Config_Media :: set($this->_index, 'g.Media', 'Media', $idart);
            $selectedImages = Moraso_Persistence_View_Media::byFileName($idart, $images);
        } else {
            $selectedImages = Moraso_Persistence_View_Media::ofSpecifiedArticle($idart);
        }

        if (!empty($defaults['attr'])) {
            $attr = new Zend_Config(Moraso_Util::object_to_array($defaults['attr']), array('allowModifications' => true));
        } else {
            $attr = new Zend_Config(array(), array('allowModifications' => true));
        }

        if ($defaults['configurable']['attr']) {
            $attr_config = Aitsu_Content_Config_Textarea::set($this->_index, 'attr', Aitsu_Translate::_('Attributes'), $translation['configuration']);
        }

        if (!empty($attr_config)) {
            $config = new Zend_Config(Moraso_Util::parseSimpleIni($attr_config)->toArray(), array('allowModifications' => true));
            $attr = $attr->merge($config);
        }

        if ($defaults['configurable']['rel']) {
            $rel = Aitsu_Content_Config_Text::set($this->_index, 'rel', Aitsu_Translate::_('rel'), $translation['configuration']);
        }

        $attr->rel = !empty($rel) ? $rel : (isset($defaults['attr']->rel) && !empty($defaults['attr']->rel)) ? $defaults['attr']->rel : $defaults['rel'];

        if (!isset($attr->style)) {
            $attr->style = new stdClass();
        }

        if ($defaults['configurable']['style']) {
            $attr->style->self = Aitsu_Content_Config_Text::set($this->_index, 'style', Aitsu_Translate::_('Style'), $translation['configuration']);
        }

        if ($defaults['configurable']['float']) {
            $floatSelect = array(
                Aitsu_Translate::_('not specified') => '',
                Aitsu_Translate::_('left') => 'left',
                Aitsu_Translate::_('right') => 'right',
                Aitsu_Translate::_('none') => 'none'
            );

            $float = Aitsu_Content_Config_Select::set($this->_index, 'float', Aitsu_Translate::_('Float'), $floatSelect, $translation['configuration']);
        }

        if (!empty($float)) {
            $attr->style->float = $float;
        }

        $customFields = array();
        for ($i = 1; $i <= $defaults['customFields']; $i++) {
            $customFields[$i] = Aitsu_Content_Config_Text::set($this->_index, 'customField_' . $i, Aitsu_Translate::_('custom field') . ' #' . $i, $translation['configuration']);
        }

        if (empty($template) || empty($selectedImages) || !in_array($template, $this->_getTemplates())) {
            return '';
        }

        $view = $this->_getView();

        $view->selectedImages = $selectedImages;
        $view->width = $width;
        $view->height = $height;
        $view->render = $render;
        $view->attributes = $attr;
        $view->customFields = $customFields;

        return $view->render($template . '.phtml');
    }

    protected function _cachingPeriod() {

        return 'eternal';
    }

}