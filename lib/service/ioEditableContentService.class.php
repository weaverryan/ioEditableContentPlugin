<?php

/**
 * Handles logic related to how editable contents should be rendered in
 * both output and edit modes.
 * 
 * @package     ioEditableContentPlugin
 * @subpackage  service
 * @author      Ryan Weaver <ryan.weaver@iostudio.com>
 */
class ioEditableContentService
{
  /**
   * @var string The class name to give editable content areas
   */
  protected $_editableClassName;

  /**
   * Class constructor
   *
   * @param  string $editableClassName The class name to give editable content areas
   */
  public function __construct($editableClassName)
  {
    $this->_editableClassName = $editableClassName;
  }

  /**
   * Returns an editable content with markup necessary to allow editing
   *
   * All options are rendered as attributes, except for these special options:
   *   * partial - A partial to render with instead of using the raw content value
   *   * form    - A form class to use for editing the content. The form class
   *               will be stripped to only include the given content. To
   *               edit entire forms, use get_editable_form().
   *
   * @param string  $tag      The tag to render (e.g. div, a, span)
   * @param mixed   $obj      A Doctrine/Propel record
   * @param string  $field    The name of the content to edit & render
   * @param array   $options  The options / attributes array (see above)
   * 
   * @return string
   */
  public function getEditableContentTag($tag, $obj, $field, $options = array())
  {
    sfApplicationConfiguration::getActive()->loadHelpers('Tag');

    // extract the partial and form variables
    $partial = _get_option($options, 'partial');
    $form = _get_option($options, 'form');

    // set the attributes variable, save the classes as an array for easier processing
    $attributes = $options;
    $classes = isset($attributes['class']) ? explode(' ', $attributes['class']) : array();

    // add in the classes needed to activate the editable content
    if ($this->shouldShowEditor())
    {
      // setup the editable class
      $classes[] = $this->_editableClassName;

      // setup an options array to be serialized as a class (jquery.metadata)
      $options = array();
      $options['model'] = get_class($obj);
      $options['pk'] = $this->_getPrimaryKey($obj);

      // if the partial is set, add it to the options
      if ($partial)
      {
        $options['partial'] = $partial;
      }

      // if the form is set, add it to the options
      if ($form)
      {
        $options['form'] = $form;
      }

      $classes[] = json_encode($options);
    }

    // if we have some classes, set them to the attributes
    if (count($classes) > 0)
    {
      $attributes['class'] = implode(' ', $classes);
    }
    $content = $this->_getContent($obj, $field);

    return content_tag($tag, $content, $attributes);
  }

  /**
   * Returns whether or not inline editing should be rendered for this request.
   *
   * @TODO implement this
   * @return boolean
   */
  public function shouldShowEditor()
  {
    return true;
  }

  /**
   * Returns the content given an orm object and field name
   *
   * @param  mixed $obj     The Doctrine/propel object that houses the content 
   * @param  string $field  The name of the field on the model to use for content
   *
   * @todo Make this work with propel
   * @return string
   */
  protected function _getContent($obj, $field)
  {
    return $obj->get($field);
  }

  /**
   * Returns the primary key of the given orm object
   *
   * @param  mixed $obj The Doctrine/propel object to get the pk from
   * @throws sfException
   *
   * @todo Make this work with propel
   * @return mixed
   */
  protected function _getPrimaryKey($obj)
  {
    // find the primary key value, or freak out if there are multiple primary keys
    $pkField = $obj->getTable()->getIdentifierColumnNames();
    if (count($pkField) > 0)
    {
      throw new sfException('Multiple primary keys are not currently supported');
    }
    
    return $obj->get($pkField[0]);
  }
}