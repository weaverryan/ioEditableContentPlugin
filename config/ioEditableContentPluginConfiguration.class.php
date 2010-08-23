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

    // create the editable content service
    $this->_editableContentService = $this->_createEditableContentService($event->getSubject()->getUser());

    // load the editor assets
    if ($this->_editableContentService->shouldShowEditor())
    {
      $this->_loadEditorAssets($event->getSubject());
    }
  }

  /**
   * Creates the editable content service class.
   *
   * @param sfUser $user
   * @return ioEditableContentService
   */
  protected function _createEditableContentService(sfUser $user)
  {
    $class = sfConfig::get('app_editable_content_content_service_class', 'ioEditableContentService');
    $options = sfConfig::get('app_editable_content_content_service_options', array());

    return new $class($user, $options);
  }


  /**
   * Loads all of the js/css necessary to support the inline editor
   *
   * @param sfWebResponse $response
   * @return void
   */
  protected function _loadEditorAssets(sfContext $context)
  {
    $response = $context->getResponse();
    $pluginWebRoot = sfConfig::get('app_editable_content_assets_web_root', '/ioEditableContentPlugin');

    // JQuery
    if (true === sfConfig::get('app_editable_content_load_jquery'))
    {
      $response->addJavascript(sprintf('%s/js/jquery-1.4.2.min.js', $pluginWebRoot), 'last');
    }

    // JQuery ui (just core and widget)
    if (true === sfConfig::get('app_editable_content_load_jquery_ui'))
    {
      $response->addJavascript(sprintf('%s/js/jquery-ui-core-widget.min.js', $pluginWebRoot), 'last');
    }

    // JQuery metadata
    if (true === sfConfig::get('app_editable_content_load_jquery_metadata'))
    {
      $response->addJavascript(sprintf('%s/js/jquery.metadata.js', $pluginWebRoot), 'last');
    }

    // JQuery form
    if (true === sfConfig::get('app_editable_content_load_jquery_form'))
    {
      $response->addJavascript(sprintf('%s/js/jquery.form.js', $pluginWebRoot), 'last');
    }

    // JQuery blockUI
    if (true === sfConfig::get('app_editable_content_load_jquery_blockui'))
    {
      $response->addJavascript(sprintf('%s/js/jquery.blockUI.js', $pluginWebRoot), 'last');
    }

    // Fancybox
    if (true === sfConfig::get('app_editable_content_load_fancybox'))
    {
      $response->addJavascript(sprintf('%s/fancybox/jquery.fancybox-1.3.1.js', $pluginWebRoot), 'last');
      $response->addStylesheet(sprintf('%s/fancybox/jquery.fancybox-1.3.1.css', $pluginWebRoot), 'last');
    }

    // The admin javascript file is handled by symfony
    $response->addJavascript(sprintf('%s/js/ioEditableContentList.js', $pluginWebRoot), 'last');
    $response->addJavascript(sprintf('%s/js/ioEditableContent.js', $pluginWebRoot), 'last');
    $response->addJavascript(sprintf('%s/js/ioContentEditor.js', $pluginWebRoot), 'last');
    $response->addJavascript($context->getController()->genUrl('@editable_content_admin_js'), 'last');

    // The admin css file is handled by symfony
    $response->addStylesheet($context->getController()->genUrl('@editable_content_admin_css'), 'first');
  }
}