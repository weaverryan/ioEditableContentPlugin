ioEditableFieldPlugin
=====================

Allows for frontend/inline editing of fields and forms. When a user is authenicated
and has the correct permission, an edit button will appear over the content.

The easiest method is to simply render a field. Suppose we have a `Blog` model:

    <h1><?php render_editable_field($blog, 'title') ?></h1>

You can also get fancier by creating entire forms that should be edited inline. In
this case, you specify a template where you render the data:

    <?php render_editable_field($blog, null, 'blog/myPartial', 'myBlogForm') ?>

The above would render the `myBlogForm` when editing. When rendering, it would call
the `blog/myPartial` partial, which might look like this:

    <div class="body">
      <?php echo $blog->content ?>
    </div>
