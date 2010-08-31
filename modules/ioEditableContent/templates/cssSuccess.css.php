/** frontend styles **/
.<?php echo $editableClassName ?> {
}

.<?php echo $editableClassName ?>:hover {
  filter:alpha(opacity=50);
  -moz-opacity:0.5;
  opacity: 0.5;
}
/* don't be transparent when the editor is opened */
.editor_opened:hover {
  filter:alpha(opacity=100);
  -moz-opacity:1.0;
  opacity: 1.0;
}

.<?php echo $editableClassName ?>:before {
  content: "[double-click to edit]";
  font-family: Arial, Helvetica, sans;
  font-size: 70%;
  color: #888;
  float: right;
  padding: .2em .2em 0 0;
}

.<?php echo $editableClassName ?> textarea, .<?php echo $editableClassName ?> input[type="text"] {
  width: 95%;
  font:bold 0.95em arial, sans-serif;
}

/* The placeholder "new" element output with editable lists */
.<?php echo $editableListClassName ?> .io_new_tag {
  display: none !important;
}

/* The delete link - hidden unless hovering over the element */
.editable_delete_link {
  background: url('<?php echo image_path('/ioEditableContentPlugin/images/delete.png') ?>') top left no-repeat;
  width: 16px;
  height: 16px;
  position: absolute;
  display: none;
}
.<?php echo $editableClassName ?>:hover .editable_delete_link {
  display: inline;
}

.<?php echo $editableClassName ?> a.add_new {
  font-style: italic;
  display: block;
  height: 16px;
  background: url('<?php echo image_path('/ioEditableContentPlugin/images/add.png') ?>') top left no-repeat;
  font-size: .8em;
  padding-left: 20px;
}