/** frontend styles **/
.<?php echo $editableClassName ?> {
}

.<?php echo $editableClassName ?>:hover {
  filter:alpha(opacity=50);
  -moz-opacity:0.5;
  opacity: 0.5;
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