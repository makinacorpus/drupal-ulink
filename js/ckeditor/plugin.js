/* jshint vars: true, forin: false, strict: true, browser: true,  jquery: true */
/* globals CKEDITOR, Ulink, Drupal, jQuery */
(function (CKEDITOR, ULink, Drupal) {
  "use strict";

  CKEDITOR.plugins.add('ulink', {

    hidpi: true,
    icons: 'ulink',

    init: function (editor) {

      editor.addCommand('ulink', new CKEDITOR.dialogCommand( 'ulinkDialog', {
        allowedContent: 'a[name,id,href,title,target]',
        requiredContent: 'a[href]'
      }));

      editor.ui.addButton('ulink', {
        label: Drupal.t("Link to content"),
        command: 'ulink'
      });

      editor.setKeystroke( CKEDITOR.CTRL + 76 /*L*/, 'ulink' );
      editor.on('doubleclick', function (evt) {
        var element = CKEDITOR.plugins.link.getSelectedLink(editor) || evt.data.element;

        if (!element.isReadOnly()) {
          if (element.is('a')) {
            evt.data.dialog = 'ulinkDialog';
            evt.data.link = element; // Pass the link to be selected along with event data.
          }
        }
      }, null, null, 0);

      CKEDITOR.dialog.add('ulinkDialog', this.path + 'dialogs/ulink.js');
    }
  });

  // Globally reachable plugin functions
  CKEDITOR.plugins.ulink = {

    /**
     * Copy/pasted from the 'link' plugin.
     */
    getSelectedLink: function (editor) {
      var selection = editor.getSelection();
      var selectedElement = selection.getSelectedElement();

      if (selectedElement && selectedElement.is('a')) {
        return selectedElement;
      }

      var range = selection.getRanges()[0];

      if (range) {
        range.shrink(CKEDITOR.SHRINK_TEXT);

        return editor.elementPath(range.getCommonAncestor()).contains('a', 1);
      }

      return null;
    }
  };

}(CKEDITOR, ULink, Drupal));
