<?php

/**
 * Plugin configuration class
 * 
 * @package     ioEditableContentPluginConfiguration
 * @subpackage  config
 * @author      Ryan Weaver <ryan.weaver@iostudio.com>
 */
class ioEditableContentPluginConfiguration extends sfPluginConfiguration
{
  /**
   * @var ioEditableContentService
   */
  protected $_editableContentService;

  /**
   * Initializes the plugin
   *
   * @return void
   */
  public function initialize()
  {
    $this->dispatcher->connect('context.load_factories', array($this, 'listenToContextLoadFactories'));
  }

  /**
   * Returns the editable content service, which acts like a singleton
   * within the current configuration instance.
   *
   * @return ioEditableContentService
   */
  public function getEditableContentService()
  {
    if ($this->_editableContentService === null)
    {
      $class = sfConfig::get('app_editable_content_content_service_class', 'ioEditableContentService');
      $this->_editableContentService = new $class();
    }

    return $this->_editableContentService;
  }

  /**
   * Automatic plugin modules and helper loading
   *
   * @param  sfEvent  $event
   */
  public function listenToContextLoadFactories(sfEvent $event)
  {
    // Enable module automatically
    sfConfig::set('sf_enabled_modules', array_merge(
      sfConfig::get('sf_enabled_modules', array()),
      array('ioEditableContent')
    ));

    // Load helper as well
    $event->getSubject()->getConfiguration()->loadHelpers(array('EditableContent'));
  }
}