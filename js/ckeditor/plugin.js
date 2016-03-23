/* jshint vars: true, forin: false, strict: true, browser: true,  jquery: true */
/* globals CKEDITOR, Ulink, Drupal, jQuery */
(function (CKEDITOR, ULink, Drupal) {
  "use strict";

  CKEDITOR.plugins.add('ulink', {

    requires : ['link'],
    hidpi: true,
    icons: 'ulink',

    init: function (editor) {

      editor.addCommand('ulink', new CKEDITOR.dialogCommand( 'ulinkDialog'));

      editor.ui.addButton('ulink', {
        label: Drupal.t("Link to content"),
        command: 'ulink'
      });

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
