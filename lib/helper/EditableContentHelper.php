<?php

/**
 * Helper class for the editable field plugin.
 * 
 * Assists in outputting the editable fields
 * 
 * @package     ioEditableFieldPlugin
 * @subpackage  helper
 * @author      Ryan Weaver <ryan.weaver@iostudio.com>
 */


/**
 * Returns an editable field with markup necessary to allow editing
 *
 * Available options include
 *   * partial - A partial to render with instead of using the raw field value
 *   * form    - A form class to use for editing the field. The form class
 *               will be stripped to only include the given field. To
 *               edit entire forms, use get_editable_form().
 *
 * @param string  $tag      The tag to render (e.g. div, a, span)
 * @param mixed   $obj      A Doctrine/Propel record
 * @param string  $field    The name of the field to edit & render
 * @param array   $options  The options array (see above)
 *
 * @return string
 */
function editable_content_tag($tag, $obj, $field, $options = array())
{
  return get_editable_field_service()->getEditableContentTag($tag, $obj, $field, $options);
}

/**
 * Shortcut to return the current editable field service.
 *
 * @return ioEditableFieldService
 */
function get_editable_field_service()
{
  return sfApplicationConfiguration()::getActive()
    ->getPluginConfiguration('ioEditableFieldServicePlugin')
    ->getEditableFieldService();
}