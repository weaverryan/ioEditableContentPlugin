ioEditableContentPlugin
=======================

This plugin is young - use at your own risk. I welcome any feedback.

Allows for frontend/inline editing of fields and forms. The goal is to
allow content to be inline-editable without getting in the way of the
developer.

    <?php echo editable_content_tag('h1', $blog, 'title') ?>

    <h1>My blog title</h1>

When a user is authenticated and has the correct permission, the editable
content blocks can be double-clicked to initiate editing.

![view](http://github.com/weaverryan/ioEditableContentPlugin/raw/master/docs/view.png "normal view mode")

![editors](http://github.com/weaverryan/ioEditableContentPlugin/raw/master/docs/editors.png "editors open")

This plugin is inspired by both:

 * [sfDoctrineEditableComponentPlugin](http://github.com/n1k0/sfDoctrineEditableComponentPlugin/blob/master/config/app.yml)
 * [sympal CMF](http://www.sympalphp.org)

Installation
------------

### With git

    git submodule add git://github.com/weaverryan/ioEditableContentPlugin.git plugins/ioEditableContentPlugin
    git submodule init
    git submodule update

### With subversion

    svn propedit svn:externals plugins

In the editor that's displayed, add the following entry and then save

    ioEditableContentPlugin https://svn.github.com/weaverryan/ioEditableContentPlugin.git

Finally, update:

    svn update

### Setup

In your `config/ProjectConfiguration.class.php` file, make sure you have
the plugin enabled.

    $this->enablePlugins('ioEditableContentPlugin');

Create a symbolic link to the web directory of the plugin by running:

    ./symfony plugin:publish-assets

Usage: Editable content
-----------------------

This plugin allows for the rendering of data from a model in such a way
that inline editing is automatic. The simplest version of this includes
rendering just one field in the view. Suppose we have a `Blog` model with
a `title` field:

    <?php echo editable_content_tag('h1', $blog, 'title') ?>

The above code would render the following html:

    <h1>Homepage</h1>

If the current user had the proper credentials, a special class would be
added to the `h1` tag that would initialize the inline editing. By using
the `editable_content_tag()` to render your content tag, you've automatically
been given the blessing of inline editing.

You an also specify attributes that should be on the main `h1` tag:

    <?php echo editable_content_tag('h1', $blog, 'title', array('class' => 'header')) ?>

    <h1 class="header">Homepage</h1>

>The `$blog` object doesn't have to be saved to the database yet - it could
>be a new `Blog`. New objects are saved to the database correctly.

### A more complex example

You can also choose to edit more than one field at once in your form. An
example of this might be if you have an anchor tag where both the `href`
and html of the anchor are fields on your model.

Assume that we have `link_title` and `link_url` fields on the `Blog` model:

    <?php echo editable_content_tag(
      'div',
      $blog,
      array('link_title', 'link_url'),
      array('partial' => 'myModule/blog_link',
    ) ?>

In this case, when the form is displayed, it will have both the `link_title`
and `link_url` fields in it

Notice that we added a fourth argument, an array with a `partial` key.
This option allows you to specify a partial that should render the content.
For example, the `myModule/blog_link` partial might look like this:

    Read more details:
    <a href="<?php echo $blog->link_url ?>"><?php echo $blog->link_title ?></a>

>When using a partial, the variable passed to the partial is the "tableized"
>version of the model name. `Blog` becomes `$blog`, `sfGuardUser` becomes
>`$sf_guard_user`. If in doubt, echo the `$var_name` variable it contains
>the string name of the variable (e.g. `blog`).

### The fourth arguments: options array

Notice that the fourth arguments, the options array, is special. This argument
allows you to both specify certain options for the editable content tag as
well as html attributes that will be added to the main tag.

All values passed to this array become attributes on the main html tag
except for the following:

 * `partial` A partial to render with instead of rendering the raw field
    from the given database object.
 
 * `form` A form class to use when editing the content. By default, the
   main form class (e.g. `BlogForm`) will be used. If a third argument
   is passed to `editable_content_tag()`, only those fields will be used
   on the form.

 * `form_partial` The partial used to render the fields of the form

 * `mode` Which type of editor to load: fancybox(default) or inline

Some of these options are explained in the next section.

### A fully-configured example

While editing and display one field is nice, this plugin allows you to
get infinitely more complex.

Suppose, that you need the edit form to be a completely custom form class
rendered via a custom form partial. For example, suppose that each `Blog`
has many `Photo` objects and you've created a form that embeds these
related `Photo` objects (an exercise we won't go into here).

    <?php echo editable_content_tag('div', $blog, null, array(
      'form'         => 'myBlogPhotoForm',
      'form_partial' => 'blog/photoForm',
      'partial       => 'blog/photos',
      'mode'         => 'inline',
    )) ?>

The above code would use `myBlogPhotoForm` as the form class. Additionally,
it would call the `blog/photoForm` partial to render the fields of that
form. For example, the `blog/photoForm` partial might look like this:

    <h2>Edit Blog Photos</h2>
    <?php foreach ($form['Photos'] as $photoField): ?>
      <div class="blog_photo">
        <?php echo $photoField->renderError(); ?>
        <?php echo $photoField->render(); ?>
      </div>
    <?php endforeach; ?>

Notice that the `form` tag, hidden fields, and submit buttons are not present.
These are all taken care of for you automatically so that you need only
to be concerned about rendering the actual fields of your form.

Like in the previous example, the `blog/photos` partial would be called
when rendering this content tag:

    <?php foreach ($blog->Photos as $photo): ?>
      <?php echo image_tag('/uploads/'.$photo->filename) ?>
    <?php endforeach; ?>

Finally, another option, `mode`, was also specified in the options argument
of `editable_content_tag`. This option, which can take the value `inline`
or `fancybox` whether to render the form inline where the actual content
resides or in a popup fancybox modal window.

Usage: Editable lists
---------------------

So far, we've talked only about editing a set of fields on one object. Sometimes,
however, you may need to output a list of objects, where each object is
editable. For example, suppose we have an "author" index page that lists
all the authors for our blog:

    <div class="authors">
      <?php foreach ($authors as $author): ?>
        <?php echo editable_content_tag('h2', $author, 'name', array('class' => 'header') ?>
      <?php endforeach; ?>
    </div>

The above code will work perfectly. However, let's add the following requirements
to this blog list page:

 * The admin should be able to add new authors inline
 * The admin should be able to delete existing authors inline
 * The admin should be able to reorder the authors inline

To do this, we introduce a new helper function: `editable_content_list()`.
This function takes a collection of objects and renders each using
`editable_content_tag()`. It also adds the above functionality (optionally)
along the way:

    <?php echo editable_content_list(
      'div',
      $authors,
      array('with_new' => true, 'class' => 'authors'),
      'h2',
      'name',
      array('class' => 'header'),
    ) ?>

The first three options should feel very similar to `editable_content_tag`
and are:

  * A tag to surround all of the entries (e.g. `div`)
  * The collection of objects to render
  * An options array - a mixture of options and attributes for the outer tag

The second three options are simply the first, third and fourth option
from `editable_content_tag`. These specify how each individual entry
of the collection should be rendered.

Configuration
-------------

Various things can be configured at the global level via `app.yml`. All
of these options are present in the `app.yml` packaged with the plugin
along with description for each.

The most interesting options occur under the `content_service_options` key:

    all:
      editable_content:
        content_service_options:
          empty_text:          "[Click to edit]"
          edit_mode:           fancybox
          admin_credential:    editable_content_admin

The `empty_text` option specifies the default text that will be placed
in any content tags that have no content.

The The `edit_mode` option takes one of two values: `fancybox` or `inline`.
This specifies the default mode to be used unless an individual content
tag passes the `mode` option.

The `admin_credential` option specifies the user credential a user must
have in order to see content editor. If set to `false`, all authenticated
users will be able to edit the content.

Using with CKEditor
-------------------

To use the plugin with CKEditor, simply change the widget in your form to
be a CKEditor widget. A great plugin for doing this is
[sfCKEditorPlugin](http://www.symfony-project.org/plugins/sfCKEditorPlugin).

While the above will work for normal forms, it will not persist correctly
for any ajax forms (which this plugin uses). By including the following
javascript, the CKEditor will persist correctly:

    jQuery(document).ready(function(){
      $('.io_editable_content').bind('preFormSubmit', function(event){
        jQuery.each(CKEDITOR.instances, function(index, value) {
          value.updateElement();
        });
      });
    });

The fine details
----------------

Please clone and improve this plugin! Any feedback is welcomed - including
design related :). If you have any ideas, notice any bugs, or have any
ideas, you can reach me at ryan [at] thatsquality.com or @weaverryan.

A bug tracker is available at
[http://redmine.sympalphp.org/projects/editable-content](http://redmine.sympalphp.org/projects/editable-content)

This plugin was taken from both [sympal CMF](http://www.sympalphp.org)
and [sfDoctrineEditableComponentPlugin](http://github.com/n1k0/sfDoctrineEditableComponentPlugin).
