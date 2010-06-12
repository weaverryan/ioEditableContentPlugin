ioEditableContentPlugin
=====================

Allows for frontend/inline editing of fields and forms. When a user is authenicated
and has the correct permission, an edit button will appear over the content.

This plugin is inspired by both:
 * [sfDoctrineEditableComponentPlugin](http://github.com/n1k0/sfDoctrineEditableComponentPlugin/blob/master/config/app.yml)
 * [sympal CMF](http://www.sympalphp.org)

Simple Examples
---------------

The easiest method is to simply render a field. Suppose we have a `Blog` model:

    <?php echo editable_content_tag('h1', $blog, 'title') ?>

    <h1>Homepage</h1>

You can also get fancier by creating entire forms that should be edited inline. In
this case, you specify a template where you render the data:

    <?php editable_content_tag('div', $blog, null, array(
      'form' => 'myBlogForm',
      'partial => 'blog/myPartial',
      'class'=> 'blog_body'
    )) ?>

The above would render the `myBlogForm` when editing. When rendering, it would call
the `blog/myPartial` partial, which might look like this:

    <?php echo $blog->content ?>

This would ultimately render the following:

    <div class="blog_body">My blog content</div>