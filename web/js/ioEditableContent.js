(function($) {

$.widget('ui.ioEditableContent', {
  
  options: {
    mode: 'fancybox'
  },
  
  _create: function() {
    this.initialize();
  },
  
  openEditor: function() {
    var self = this;
    
    // disable the non-edit-mode controls
    this._disableControls();
    
    /*
     * 1) Ajax load the contents into the editor element
     * 2) Calls initializeEditor, which creates a editor object
     */
    if (this.option('mode') == 'inline')
    {
      self.element.load(self._getFormUrl(), function() {
        self._initializeEditor(self.element);
      });
    }
    else
    {
      $.fancybox(self._getFormUrl(), {
        'zoomSpeedIn': 300,
        'zoomSpeedOut': 300,
        'overlayShow': true,
        'autoDimensions': false,
        'hideOnContentClick': false,
        'type': 'ajax',
        'onComplete': function() {
          self._initializeEditor($('#fancybox-inner'));

            // WARNING: this line may cause problems with WYSIWYG editors (as far as it screwing up the size)
            // makes the window fit around the internal. This is needed
            // here because it means we've given the ajax'ed content a chance
            // to do its own javascript and resize its internals
            //$.fancybox.resize();
        },
        'onCleanup': function() {
          self.closeEditor();
        }
      });
    }
  },
  
  closeEditor: function() {
    var self = this;
    
    if (!this.getEditor())
    {
      return;
    }
    
    // kill the editor
    var editor = this.getEditor();
    this._setOption('content_editor', null);

    
    self.blockUI();
    
    // ajax in the content, and then set things back up
    $(self.element).load(
      self._getShowUrl(),
      function() {
        // reinitialize the non-edit-mode controls
        self._enableControls();
        
        // make sure fancybox is closed
        $.fancybox.close();
        
        // destroy the editor
        editor.ioContentEditor('destroy');
        
        self.unblockUI();
        
        // throw a close event to listen onto
        self.element.trigger('closeEditor');
      }
    );
  },
  
  initialize: function() {
    var self = this;
    
    // register non-edit-handlers: effects for when the slot is not being edited    
    this._initializeControls();
    // attach the nonEditHandler events
    this._enableControls();
  },
  
  _initializeEditor: function(editorSelector) {
    // initializes the editor object on the given selector
    var self = this;
    
    editorSelector.ioContentEditor({});
    
    editorSelector.bind('close', function() {
      self.closeEditor();
    });

    // store the content_editor
    this._setOption('content_editor', editorSelector);
    
    // throw a close event to listen onto
    self.element.trigger('openEditor');
  },
  
  _initializeControls: function() {
    // initializes the edit controls
    var self = this;
    
    control_events = {};
    control_events['dblclick'] = function() {
      self.openEditor()
    }
    this._setOption('control_events', control_events);
  },
  
  _enableControls: function() {
    var self = this;
    
    // bind all of the non-edit-mode handlers
    $.each(this.option('control_events'), function(key, value) {
      self.element.bind(key, value);
    });
  },
  
  _disableControls: function() {
    var self = this;
    
    // disable all of the non-edit-mode handlers
    $.each(this.option('control_events'), function(key, value) {
      self.element.unbind(key, value);
    });
  },

  _getFormUrl: function(){
    return this.option('form_url')+'?'+this._getUrlQueryString();
  },

  _getShowUrl: function(){
    return this.option('show_url')+'?'+this._getUrlQueryString();
  },

  _getUrlQueryString: function(){
    // returns the common query string needed for the form and show urls
    var params = {};
    params.fields = this.option('fields');
    params.form_url = this.option('form_url');
    params.model = this.option('model');
    params.pk = this.option('pk');

    return jQuery.param(params)
  },
  
  getEditor: function(){
    return this.option('content_editor');
  },

  blockUI: function() {
    if ($.isFunction('blockUI'))
    {
      $.blockUI();
    }
  },

  unblockUI: function() {
    if ($.isFunction('unblockUI'))
    {
      $.unblockUI();
    }
  }
  
});

})(jQuery);