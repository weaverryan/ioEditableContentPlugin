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
   * @var sfBasicSecurityUser
   */
  protected $_user;

  /**
   * @var sfEventDispatcher
   */
  protected $_dispatcher;

  /**
   * @var array The global options array
   *
   * Default options include:
   *  * editable_class_name: The class name to give editable content areas
   *  * edit_mode:           The default edit mode
   *  * empty_text:          The text to render for empty content
   *  * admin_credential:    The credential needed to trigger the editor
   */
  protected $_options = array();

  /**
   * @var array
   */
  protected $_shouldShowEditor = array();

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
    'with_delete',
    'method',
    'default_values'
  );

  protected $_validListOptions = array(
    'with_new',
    'with_delete',
    'sortable',
    'add_new_label',
  );

  /**
   * Class constructor
   *
   * @param  string $editableClassName  The class name to give editable content areas
   * @param  string $defaultMode        The default editor mode
   */
  public function __construct(sfBasicSecurityUser $user, sfEventDispatcher $dispatcher, $options = array())
  {
    $this->_user = $user;
    $this->_dispatcher = $dispatcher;
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
    if ($this->shouldShowEditor($obj))
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
    $method = isset($options['method']) ? $options['method'] : null;
    $content = $this->getContent($obj, $fields, $partial, $method);

    // if we have some classes, set them to the attributes
    if (count($classes) > 0)
    {
      $attributes['class'] = implode(' ', $classes);
    }

    return content_tag($tag, $content, $attributes);
  }
  
  /**
   * Iterates through and renders a collection of objects, each wrapped with
   * its own editable_content_tag.
   *
   * The advantage of using this method instead of manually iterating through
   * a collection and using editable_content_tag() is that this method adds
   * collection-specific functionality such as "Add new" and sortable.
   *
   * @param string $outer_tag The tag that should surround the whole collection (e.g. ul)
   * @param mixed $collection The Doctrine_Collection to iterate and render
   * @param array $options    An array of options to configure the outer tag
   * @param string $inner_tag The tag to render around each item (@see editable_content_tag)
   * @param mixed $fields     The field or fields to render and edit for each item (@see editable_content_tag)
   * @param array $inner_options Option on each internal editable_content_tag (@see editable_content_tag)
   *
   * @return string
   */
  public function getEditableContentList($outer_tag, Doctrine_Collection $collection, $attributes, $inner_tag, $fields, $inner_attributes)
  {
    // extract the option values, remove from the attributes array
    $options = array();
    foreach ($this->_validListOptions as $validOption)
    {
      if (isset($attributes[$validOption]))
      {
        $options[$validOption] = _get_option($attributes, $validOption);
      }
    }

    // pass the special with_delete option to the inner attributes
    $inner_attributes['with_delete'] = _get_option($options, 'with_delete');

    // start decking out the classes on the outer tag
    $classes = isset($attributes['class']) ? explode(' ', $attributes['class']) : array();

    if ($this->shouldShowEditor($collection))
    {
      $classes[] = json_encode($options);
      $classes[] = $this->getOption('editable_list_class_name', 'io_editable_content_list');
    }

    if (count($classes))
    {
      $attributes['class'] = implode(' ', $classes);
    }
    
    // create a new object of the given model
    $class = $collection->getTable()->getClassNameToReturn();
    $new = new $class();

    /*
     * Begin rendering the content - this is a refactor of the previous
     * _list.php partial
     */

    $content = '';
    foreach ($collection as $object)
    {
      $content .= $this->getEditableContentTag($inner_tag, $object, $fields, $inner_attributes);
    }

    // add the empty/new item so the js has something to build from
    if ($this->shouldShowEditor($collection))
    {
      $empty_attributes = $inner_attributes;
      if (isset($empty_attributes['class']))
      {
        $empty_attributes['class'] = $empty_attributes['class'] .' io_new_tag';
      }
      else
      {
        $empty_attributes['class'] = 'io_new_tag';
      }

      $content .= $this->getEditableContentTag($inner_tag, $new, $fields, $empty_attributes);
    }

    // actually render the outer tag
    $content = content_tag($outer_tag, $content, $attributes);

    return $content;
  }
  
  /**
   * Returns whether or not inline editing should be enabled.
   *
   * This method can be called "in general" (no $obj passed) or answered
   * for a very specific object being modified.
   *
   * @param Object $object The Object being edited - could be a Doctrine_Record, Doctrine_Collection 
   * @return boolean
   */
  public function shouldShowEditor($obj = null)
  {
    $key = ($obj === null) ? 'generic' : spl_object_hash($obj);

    if (!isset($this->_shouldShowEditor[$key]))
    {
      $credential = $this->getOption('admin_credential');
      if ($credential)
      {
        $shouldShow = $this->_user->hasCredential($credential);
      }
      else
      {
        // even if no credential were passed, still require a login at least
        $shouldShow = $this->_user->isAuthenticated();
      }

      $event = new sfEvent($this, 'editable_content.should_show_editor', array(
        'user'    => $this->_user,
        'object'  => $obj,
      ));
      $this->_dispatcher->filter($event, $shouldShow);

      $this->_shouldShowEditor[$key] = $event->getReturnValue();
    }

    return $this->_shouldShowEditor[$key];
  } 

  /**
   * Returns the content given an orm object and field name
   *
   * @param  mixed $obj     The Doctrine/propel object that houses the content 
   * @param  array $fields  The name of the fields on the model to use for content
   * @param  string $partial Optional partial to use for rendering
   * @param  string $method A method to call on the object to render a single field
   *
   * The "partial" option is the strongest (and required for more than one
   * field) - if specified, that partial will be rendered. Otherwise,
   * "method" will be called on the object and if not specified, the normal
   * getter on the one field will be called. 
   *
   * @todo Make this work with propel
   * @return string
   */
  public function getContent($obj, $fields, $partial = null, $method = null)
  {
    if ($obj instanceof sfOutputEscaper)
    {
      $obj = $obj->getRawValue();
    }

    // unless we have exactly one field, we need a partial to render
    if (count($fields) > 1 && !$partial && !$method)
    {
      throw new sfException('You must pass a "partial" option for multi-field content areas.');
    }

    if ($partial)
    {
      // render via the partial if available
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

      $content = get_partial($partial, array('var_name' => $varName, $varName => $obj));
    }
    else if ($method)
    {
      // use the method option on the object itself if available
      $content = $obj->$method();
    }
    else
    {
      // take a guess at the content since there's only one field
       $content = $obj->get($fields[0]);
    }

    // if we have content, return it
    if ($content)
    {
      return $content;
    }

    // render the default text if the editor is shown
    return $this->shouldShowEditor() ? $this->getOption('empty_text', '[Click to edit]') : '';
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