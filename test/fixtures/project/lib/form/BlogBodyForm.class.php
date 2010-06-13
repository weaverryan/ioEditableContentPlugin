<?php

// special form class for testing
class BlogBodyForm extends BlogForm
{
  public function setup()
  {
    parent::setup();

    $this->useFields(array('body'));

    $this->widgetSchema['body']->setAttribute('rows', 20);
    $this->widgetSchema['body']->setAttribute('cols', 70);
  }
}