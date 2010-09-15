<?php

require_once dirname(__FILE__).'/../../bootstrap/functional.php';
require_once $_SERVER['SYMFONY'].'/vendor/lime/lime.php';

$t = new lime_test();

$t->info('1 - Test some basic getters and setters');
  $service = new ioEditableContentService($context->getUser(), array('test_option' => 'test_val'));
  $t->is($service->getOption('test_option', 'default'), 'test_val', '->getOption() works for options passed in the constructor');
  $service->setOption('new_option', 'foo');
  $t->is($service->getOption('new_option'), 'foo', '->setOption() sets the option value correctly');
  $t->is($service->getOption('fake_option', 'bar'), 'bar', '->getOption() on a nonexistent option returns the default value.');

$t->info('2 - Test shouldShowEditor()');
  $service = new ioEditableContentService($context->getUser());
  $t->is($service->shouldShowEditor(), false, '->shouldShowEditor() returns false: no credential passed in, but we still require auth.');
  $context->getUser()->setAuthenticated(true);
  $t->is($service->shouldShowEditor(), true, '->shouldShowEditor() returns true if no credential is passed to require.');
  $service->setOption('admin_credential', 'test_edit_credential');
  $t->is($service->shouldShowEditor(), false, '->shouldShowEditor() returns false: the user does not have the credential');
  $context->getUser()->addCredential('test_edit_credential');
  $t->is($service->shouldShowEditor(), true, '->shouldShowEditor() returns true: the user has the proper credential');

$t->info('3 - Test getContent()');
  $service = new ioEditableContentService($context->getUser());
  $blog = new Blog();
  $blog->title = 'Unit test blog';
  $blog->body = 'Lorem Ipsum';
  $blog->save();

  $t->info('  3.1 - Test a single-field, no partial rendering');
  $result = $service->getContent($blog, array('title'));
  $t->is($result, 'Unit test blog', '->getContent() returns the correct value');

  $t->info('  3.2 - Test multiple fields, no partial - throws an exception');
  try
  {
    $service->getContent($blog, array('title', 'body'), null, null);
    $t->fail('Exception not thrown');
  }
  catch (sfException $e)
  {
    $t->pass('Exception thrown');
  }

  $t->info('  3.2.1 - Test multiple fields, no partial, yes "method" - does not throw exception');
  try
  {
    $service->getContent($blog, array('title', 'body'), null, 'bogus');
    $t->pass('Exception not thrown');
  }
  catch (sfException $e)
  {
    $t->fail('Exception thrown');
  }

  $t->info('  3.3 - Test with rendering a partial');
  $result = $service->getContent($blog, array(), 'unit/blog');
  $expected = '<div class="var_name">blog</div>
<div class="obj_class">Blog</div>
<div class="content">Unit test blog</div>';
  $t->is($result, $expected, '->getContent() returns content rendered from the partial');

  $t->info('  3.4 - Test with the "method" option');
  $result = $service->getcontent($blog, array(), null, 'getTestValue');
  $t->is($result, 'unit_test_value', 'The method option (and no partial option) calls the method on the object.');

$t->info('4 - Test getEditableContentTag()');

  $t->info('  4.1 - Test without proper edit credentials');
  $context->getUser()->setAuthenticated(false);
  $service = new ioEditableContentService($context->getUser());
  $result = $service->getEditableContentTag('div', $blog, 'title');
  $t->is($result, '<div>Unit test blog</div>', 'Without proper credentials, getEditableContentTag() returns just the tag and content');

  $t->info('  4.2 - single-field, no options, div');
  $context->getUser()->setAuthenticated(true);
  test_tag_creation($t, $service, 'div', 'title');

  $t->info('  4.3 - single-field, with options and attributes');
  $options = array(
    'mode' => 'test_mode',
    'partial' => 'unit/blog',
  );
  $attributes = array(
    'class' => 'my_class',
    'id' => 'testing_id',
  );
  test_tag_creation($t, $service, 'div', 'title', $options, $attributes);

  $t->info('  4.4 - Pass in a null field.');
  $result = $service->getEditableContentTag('div', $blog, null, array('partial' => 'unit/blog', 'id' => 'unit_test'));
  check_json_array($t, $result, 'fields', array());
  check_json_array($t, $result, 'mode', 'fancybox');
  check_attribute($t, $result, 'id', 'unit_test');
  $matched = strpos($result, $expected) !== false;
  $t->is($matched, true, '->getEditableContentTag() calls ->getContent() correctly.');

// test the result of getEditableContentTag() for all of the correct markup
// Used just a few times to test the full string, is a little awkward
function test_tag_creation(lime_test $t, ioEditableContentService $service, $tag, $fields, $options = array(), $attributes = array())
{
  $blog = Doctrine_Query::create()->from('Blog')->fetchOne();

  // get the actual result, leave only the opening tag, which is all we care about 
  $combined = array_merge($options, $attributes);
  $result = $service->getEditableContentTag($tag, $blog, $fields, $combined);
  $matches = array();
  preg_match('#\<'.$tag.'([^\>]+)\>#', $result, $matches);
  $result = $matches[0];

  if (isset($attributes['class']))
  {
    $attributes['class'] .= ' '. $service->getOption('editable_class_name', 'io_editable_content');
  }
  else
  {
    $attributes['class'] = $service->getOption('editable_class_name', 'io_editable_content');
  }

  if (!isset($options['mode']))
  {
    $options['mode'] = $service->getOption('edit_mode', 'fancybox');
  }
  
  $options['model'] = 'Blog';
  $options['pk'] = $blog->id;
  $options['fields'] = (array) $fields;
  $attributes['class'] .= ' '.json_encode($options);
  
  // get the result, leave only the opening tag, which is all we care about
  $expected = content_tag($tag, 'anything', $attributes);
  $expected = str_replace('anything</'.$tag.'>', '', $expected);

  $t->is($result, $expected, 'Opening tag rendered correctly: '.$result);
}

// checks a full result string for a particular name => value pair that should be in the metadata json
function check_json_array(lime_test $t, $result, $name, $value)
{
  $arr = array($name => $value);
  $json = json_encode($arr);
  $json = substr($json, 1);
  $json = substr($json, 0, strlen($json) - 1);
  $json = htmlentities($json);

  $t->like($result, '/'.preg_quote($json).'/', sprintf('->result contains json value %s: %s', $name, $value));
}

// checks to see if a particular attribute is present
function check_attribute(lime_test $t, $result, $attribute, $value)
{
  $str = sprintf('%s="%s"', $attribute, $value);

  $t->like($result, '/'.preg_quote($str).'/', sprintf('->result contains attribute %s="%s"', $attribute, $value));
}