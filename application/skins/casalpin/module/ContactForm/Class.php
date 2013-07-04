<?php

/**
* @author Frank Ammari, meine experten GbR
* @copyright Copyright &copy; 2011, meine experten GbR
*/

class Skin_Module_ContactForm_Class extends Aitsu_Module_Abstract {
    
   public static function init($context) {

          //Aitsu_Content_Edit :: noEdit('Contact', true);

          $index = $context['index'];

          $instance = new self();

          $defaultRedirectTo = Aitsu_Registry :: get()->config->contenido->webpath.substr(Aitsu_Core_Rewrite::create(array('idart' => 126)),2,-1);

          $redirectTo = Aitsu_Content_Config_Text :: set($index, 'redirectTo', '', 'Redirect');
          $senderMail = Aitsu_Content_Config_Text :: set($index, 'senderMail', 'E-Mail', 'Sender');
          $senderName = Aitsu_Content_Config_Text :: set($index, 'senderName', 'Name', 'Sender');
          $receipientMail = Aitsu_Content_Config_Text :: set($index, 'receipientMail', 'E-Mail', 'Receipient');
          $receipientName = Aitsu_Content_Config_Text :: set($index, 'receipientName', 'Name', 'Receipient');
          $subject = Aitsu_Content_Config_Text :: set($index, 'ContactSubject', 'Subject', 'Form');

          $redirectTo = $redirectTo ? $redirectTo : $defaultRedirectTo;
          $senderMail = $senderMail ? $senderMail : '';
          $senderName = $senderName ? $senderName : '';
          $receipientMail = $receipientMail ? $receipientMail : '';
          $receipientName = $receipientName ? $receipientName : '';
          $subject = $subject ? $subject : Aitsu_Translate :: _('Direct contact');

          $view = $instance->_getView();

          $view->redirectTo = $redirectTo;
          $view->subject = $subject;

          $cf = Aitsu_Form_Validation :: factory('contactForm');

             $cf->setValidator('Name', 'NoTags', array('maxlength' => 100), true);
             $cf->setValidator('Anreise', 'NoTags', array('maxlength' => 30), false);
             $cf->setValidator('Abreisedatum', 'NoTags', array('maxlength' => 30), true);
             $cf->setValidator('Email', 'Email', null, true);

          $cf->process(Aitsu_Form_Processor_Email :: factory($redirectTo, array (
             'sendermail' => $senderMail,
             'sendername' => $senderName,
             'recepientmail' => $receipientMail,
             'recepientname' => $receipientName,
             'subject' => $subject . ' :: ' . Aitsu_Util :: getCurrentUrl()
          )));

          $output = $view->render('index.phtml');

          return $output;
       }
    }