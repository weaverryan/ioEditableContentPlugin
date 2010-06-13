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
   * @var array The options array
   *
   * Default options include:
   *  * editable_class_name: The class name to give editable content areas
   *  * edit_mode:           The default edit mode
   *  * empty_text:          The text to render for empty content
   *  * admin_credential:    The credential needed to trigger the editor
   */
  protected $_options = array();

  /**
   * The valid options that can be passes in through the attributes array
   *
   * @var array
   */
  protected $_validOptions = array(
    'partial',
    'form',
    'form_partial',
    'mode',
  );

  /**
   * Class constructor
   *
   * @param  string $editableClassName  The class name to give editable content areas
   * @param  string $defaultMode        The default editor mode
   */
  public function __construct(sfBasicSecurityUser $user, $options = array())
  {
    $this->_user = $user;
    $this->_options = $options;
  }

  /**
   * Returns an editable content with markup necessary to allow editing
   *
   * All options are rendered as attributes, except for these special options:
   *   * partial - A partial to render with instead of using the raw content value
   *   * form    - A form class to use for editing the content. The form class
   *               will be stripped to only include the given content. To
   *               edit entire forms, use get_editable_form().
   *   * form_partial - The partial used to render the fields of the form
   *   * mode    - Which type of editor to load: fancybox(default)|inline
   *
   * @param string  $tag        The tag to render (e.g. div, a, span)
   * @param mixed   $obj        A Doctrine/Propel record
   * @param mixed   $fields     The field or fields to edit
   * @param array   $attributes The options / attributes array (see above)
   * @param sfBasicSecurityUser $user The user we're rendering for
   * 
   * @return string
   */
  public function getEditableContentTag($tag, $obj, $fields, $attributes = array())
  {
    if (!is_object($obj))
    {
      throw new sfException('Non-object passed, expected a Doctrine or propel object.');
    }
    sfApplicationConfiguration::getActive()->loadHelpers('Tag');

    // make sure that fields is an array
    $fields = (array) $fields;

    // extract the option values, remove from the attributes array
    $options = array();
    $options['mode'] = _get_option($attributes, 'edit_mode', $this->getOption('edit_mode', 'fancybox'));
    foreach ($this->_validOptions as $validOption)
    {
      if (isset($attributes[$validOption]))
      {
        $options[$validOption] = _get_option($attributes, $validOption);
      }
    }

    // set the attributes variable, save the classes as an array for easier processing
    $classes = isset($attributes['class']) ? explode(' ', $attributes['class']) : array();

    // add in the classes needed to activate the editable content
    if ($this->shouldShowEditor())
    {
      // setup the editable class
      $classes[] = $this->getOption('editable_class_name', 'io_editable_content');

      // setup an options array to be serialized as a class (jquery.metadata)
      $options['model'] = $this->_getObjectClass($obj);
      $options['pk'] = $this->_getPrimaryKey($obj);
      $options['fields'] = $fields;

      $classes[] = json_encode($options);
    }

    // render the html for this content tag
    $partial = isset($options['partial']) ? $options['partial'] : null;
    $content = $this->getContent($obj, $fields, $partial);

    // if we have some classes, set them to the attributes
    if (count($classes) > 0)
    {
      $attributes['class'] = implode(' ', $classes);
    }

    return content_tag($tag, $content, $attributes);
  }

  /**
   * Returns whether or not inline editing should be rendered for this request.
   *
   * @return boolean
   */
  public function shouldShowEditor()
  {
    $credential = $this->getOption('admin_credential');
    if ($credential)
    {
      return $this->_user->hasCredential($credential);
    }
    else
    {
      // even if no credential were passed, still require a login at least
      return $this->_user->isAuthenticated();
    }

    return true;
  }

  /**
   * Returns the content given an orm object and field name
   *
   * @param  mixed $obj     The Doctrine/propel object that houses the content 
   * @param  array $fields  The name of the fields on the model to use for content
   * @param  string $partial Optional partial to use for rendering
   *
   * @todo Make this work with propel
   * @return string
   */
  public function getContent($obj, $fields, $partial = null)
  {
    if ($partial)
    {
      sfApplicationConfiguration::getActive()->loadHelpers('Partial');

      /*
       * Send the object to the view with as "tableized" version of the model.
       * For example:
       *  * Blog => $blog
       *  * sfGuardUser => $sf_guard_user
       *
       * In case of confusion, another variable, $var_name, is passed, which
       * is the actual string that the variable is set to.
       */
      $varName = sfInflector::underscore($this->_getObjectClass($obj));
      return get_partial($partial, array('var_name' => $varName, $varName => $obj));
    }

    // unless we have exactly one field, we need a partial to render
    if (count($fields) != 1)
    {
      if (!$partial)
      {
        throw new sfException('You must pass a "partial" option for multi-field content areas.');
      }
    }

    if ($content = $obj->get($fields[0]))
    {
      return $content;
    }

    return $this->getOption('empty_text', '[Click to edit]');
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
    if (count($pkField) > 1)
    {
      throw new sfException('Multiple primary keys are not currently supported');
    }
    
    return $obj->get($pkField[0]);
  }

  /**
   * Returns the class that should be used to retrieve the object on the
   * next request. The $obj may be escaped.
   *
   * @param  mixed $obj The orm object
   * @todo Make this work with propel
   * @return string
   */
  protected function _getObjectClass($obj)
  {
    return $obj->getTable()->getClassNameToReturn();
  }

  /**
   * Returns an option value
   *
   * @param  string $name The option to return
   * @param  mixed  $default The default to return if the option does not exist
   * @return mixed
   */
  public function getOption($name, $default = null)
  {
    return isset($this->_options[$name]) ? $this->_options[$name] : $default;
  }

  /**
   * Sets an option value
   *
   * @param  string $name The name of the option to set
   * @param  mixed $value The value to set
   * @return void
   */
  public function setOption($name, $value)
  {
    $this->_options[$name] = $value;
  }
}