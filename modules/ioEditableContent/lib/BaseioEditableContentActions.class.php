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
    $this->editableClassName = $this->_getEditableContentService()
      ->getOption('editable_class_name', 'io_editable_content');
  }

  // the dynamic css file
  public function executeCss(sfWebRequest $request)
  {
    $this->_checkCredentials();
    $this->setLayout(false);
  }

  // the dynamic js file
  public function executeJs(sfWebRequest $request)
  {
    $this->_checkCredentials();
    $this->setLayout(false);
  }

  /**
   * Action that renders a particular inline edit form
   */
  public function executeForm(sfWebRequest $request)
  {
    $this->_checkCredentials();
    if (!$this->_setupVariables($request))
    {
      return sfView::NONE;
    }
  }

  /**
   * Handles the form submit for the form
   */
  public function executeUpdate(sfWebRequest $request)
  {
    $this->_checkCredentials();
    if (!$this->_setupVariables($request))
    {
      return sfView::NONE;
    }

    $formName = $this->form->getName();
    $this->form->bind($request->getParameter($formName), $request->getFiles($formName));

    // response is a json with an error key
    $json = array();
    if ($this->form->isValid())
    {
      $json['error'] = false;

      $isNew = ($this->form->isNew());
      $this->form->save();

      // report back the pk so we can update the original metadata value
      if ($isNew)
      {
        // dirty way to get the primary key, and then get its value - is there a better way?
        $pkField = $this->form->getObject()->getTable()->getIdentifierColumnNames();
        $pkField = $pkField[0];
        $json['pk'] = $this->form->getObject()->get($pkField);
      }
    }
    else
    {
      $json['error'] = sprintf(
        'There were %s errors when submitting the form.',
        count($this->form->getErrorSchema()->getErrors())
      );
    }

    $formPartial = $request->getParameter('form_partial', 'ioEditableContent/formFields');

    // the form body consists of both global errors and the form field partial
    $json['response'] = $this->form->renderGlobalErrors();
    $json['response'] .= $this->getPartial($formPartial);
    $text = json_encode($json);

    /*
     * If there is a file upload field, then this was submitted via an
     * iframe. To handle json response, the jquery form plugin allows us
     * to return the json inside a textarea tag
     */
    if ($this->form->isMultipart())
    {
      $text = '<textarea>'.$text.'</textarea>';
    }

    $this->renderText($text);

    return sfView::NONE;
  }

  /**
   * The ajax action the re-renders the content of an area
   */
  public function executeShow(sfWebRequest $request)
  {
    $this->_checkCredentials();
    if (!$this->_setupVariables($request))
    {
      return sfView::NONE;
    }
    $service = $this->_getEditableContentService();

    // render the content of the tag
    $this->renderText($service->getContent(
      $this->object,
      $this->fields,
      $this->partial
    ));

    return sfView::NONE;
  }

  /**
   * Ajax action that sorts editable content list
   */
  public function executeSort(sfWebRequest $request)
  {
    // give me the class of the objects being sorted
    $class = $request->getParameter('class');
    
    // give me an array where object id => position
    $sort = array_flip($request->getParameter('item'));
    
    // give me a comma delimited id string
    $ids = sprintf('(%s)', implode(',', array_keys($sort)));
    
    // retrieve the objects by the ids submitted
    $objects = Doctrine_Query::create()->from($class.' c')->where('c.id IN '.$ids)->execute();
    
    // set the positions and save the objects
    foreach($objects as $obj)
    {
      $obj->position = $sort[$obj->id];
      $obj->save();
    }
    
    return sfView::NONE;
  }
  
  public function executeDelete(sfWebRequest $request)
  {
    $id = $request->getParameter('id');
    $class = $request->getParameter('class');
    
    $obj = Doctrine_Core::getTable($class)->find($id);
    
    if ($obj)
    {
      $obj->delete();
    }
    
    return sfView::NONE;
  }

  /**
   * Returns the form object based on the request parameters
   *
   * @param sfWebRequest $request
   * @return sfForm
   */
  protected function _setupVariables(sfWebRequest $request)
  {
    $this->model = $request->getParameter('model');
    $this->pk = $request->getParameter('pk');

    $this->formClass = $request->getParameter('form', $this->model.'Form');
    $this->formPartial = $request->getParameter('form_partial', 'ioEditableContent/formFields');
    $this->fields = (array)$request->getParameter('fields', array());

    $this->partial = $request->getParameter('partial');

    // @todo make this work with propel
    $this->forward404Unless($this->model && $this->pk);
    $this->object = Doctrine_Core::getTable($this->model)->find($this->pk);
    if (!$this->object)
    {
      $this->object = new $this->model();
    }

    if (!class_exists($this->formClass))
    {
      $this->renderText(sprintf('<div>Cannot find form class "%s"</div>', $this->formClass));
      return false;
    }

    $this->form = new $this->formClass($this->object);
    if ($this->fields)
    {
      $this->form->useFields($this->fields);
    }

    $this->setLayout(false);

    return true;
  }
  
  /**
   * Helper to forward 404 if the user doesn't have edit credentials
   */
  protected function _checkCredentials()
  {
    $this->forward404Unless($this->_getEditableContentService()->shouldShowEditor($this->getUser()));
  }

  /**
   * @return ioEditableContentService
   */
  protected function _getEditableContentService()
  {
    return $this->getContext()
      ->getConfiguration()
      ->getPluginConfiguration('ioEditableContentPlugin')
      ->getEditableContentService();
  }  
}