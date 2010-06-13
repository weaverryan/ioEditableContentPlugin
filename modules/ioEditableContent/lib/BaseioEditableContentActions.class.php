<?php

/**
 * Actions class for editable content
 * 
 * @package     ioEditableContentPlugin
 * @subpackage  actions
 * @author      Ryan Weaver <ryan.weaver@iostudio.com>
 */
class BaseioEditableContentActions extends sfActions
{
  public function preExecute()
  {
    $this->pluginWebRoot = sfConfig::get('app_editable_content_assets_web_root', '/ioEditableContentPlugin');
    $this->componentCssClassName = $this->getEditableContentService()
      ->getOption('editable_class_name', 'io_editable_content');
  }

  // the dynamic css file
  public function executeCss(sfWebRequest $request)
  {
    $this->_checkCredentials();
  }

  // the dynamic js file
  public function executeJs(sfWebRequest $request)
  {
    $this->_checkCredentials();
  }

  /**
   * Helper to forward 404 if the user doesn't have edit credentials
   */
  protected function _checkCredentials()
  {
    $this->forward404Unless($this->getEditableContentService()->shouldShowEditor($this->getUser()));
  }

  /**
   * @return ioEditableContentService
   */
  protected function getEditableContentService()
  {
    return $this->getContext()
      ->getConfiguration()
      ->getPluginConfiguration('ioEditableContentPlugin')
      ->getEditableContentService();
  }  
}