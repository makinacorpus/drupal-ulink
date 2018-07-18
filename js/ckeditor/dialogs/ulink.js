/* jshint vars: true, forin: false, strict: true, browser: true,  jquery: true */
/* globals CKEDITOR Ulink Drupal jQuery */
(function (CKEDITOR, Drupal) {
  "use strict";

  CKEDITOR.dialog.add('ulinkDialog', function (editor) {

    var plugin = CKEDITOR.plugins.ulink;

    return {
      title: Drupal.t("Link to content"),
      minWidth: 400,
      minHeight: 200,
      resizable: CKEDITOR.DIALOG_RESIZE_NONE,
      contents: [
        {
          id: 'tab-content',
          label: Drupal.t('Content selection'),
          elements: [
            {
              type: 'text',
              id: 'href',
              label: Drupal.t('Link URI'),
              validate: CKEDITOR.dialog.validate.notEmpty(Drupal.t("Link "))
            },
            {
              type: 'text',
              id: 'text',
              label: Drupal.t('Link text'),
              validate: CKEDITOR.dialog.validate.notEmpty(Drupal.t("Link text cannot be empty"))
            },
            {
              type: 'text',
              id: 'title',
              label: Drupal.t('Link title for accessibility')
            },
            {
              type: 'select',
              id: 'target',
              label: Drupal.t('Link target'),
              'default': '',
              items: [
                [ Drupal.t("None"), '' ],
                [ Drupal.t("New window"), 'blank' ]
              ]
            }
          ]
        }
      ],

      onShow: function () {

        var editor = this.getParentEditor();
        var selection = editor.getSelection();
        var currentLink = plugin.getSelectedLink(editor);
        var selectedElement = selection.getSelectedElement();
        var selectionIsLink = currentLink && currentLink.hasAttribute('href');
        var isEmpty = /^\s*$/.test(selection.getSelectedText());

        if (selectionIsLink) {
          // Don't change selection if some element is already selected.
          // For example - don't destroy fake selection.
          if (!selection.getSelectedElement()) {
            selection.selectElement(currentLink);
          }
        } else {
          currentLink = null;
        }

        // Fill in values from the current selection
        var hrefElement = this.getContentElement('tab-content', 'href');
        var textElement = this.getContentElement('tab-content', 'text');
        var titleElement = this.getContentElement('tab-content', 'title');
        var targetElement = this.getContentElement('tab-content', 'target');

        if (selectionIsLink) {
          hrefElement.setValue(currentLink.getAttribute('href'));
          titleElement.setValue(currentLink.getAttribute('title'));
          targetElement.setValue(currentLink.getAttribute('target'));
        }
        if (currentLink || selectedElement || !isEmpty) {
          // Disable text, we have a selection already
          textElement.setValue("...");
          textElement.disable();
        }

        // Prepare the link to content feature
        hrefElement.getInputElement().$.placeholder = Drupal.t("Please type in at least 3 letters...");
        ULink.selector.attach(hrefElement.getInputElement().$, function (result, input) {
          if (!result || !result.title) { // Filter out invalid results
            return;
          }
          if (result.id && result.type) {
            // Do not override previous user input
            if (!textElement.getValue()) {
              // Convert HTML entities
              textElement.setValue(jQuery("<div/>").html(result.title).text());
            }
            // Because we do have problems with ckeditor URL parsing, we need to
            // force it to NOT match our protocol, hence the {{...}}, this also
            // means that our PHP server side parser must also match this
            hrefElement.setValue('{{' + result.type + '/' + result.id  + '}}');
          }
        });
      },

      // This mostly duplicates code from the 'link' plugin.
      onOk: function () {

        var data = {};
        this.commitContent(data);

        var editor = this.getParentEditor();
        var selection = editor.getSelection();
        var currentLink = plugin.getSelectedLink(editor);
        var selectedElement = selection.getSelectedElement();
        var selectionIsLink = currentLink && currentLink.hasAttribute('href');

        if (selectionIsLink) {
          // Don't change selection if some element is already selected.
          // For example - don't destroy fake selection.
          if (!selectedElement) {
            selection.selectElement(currentLink);
          }
        } else {
          currentLink = null;
        }

        var hrefValue = this.getContentElement('tab-content', 'href').getValue();
        var titleValue = this.getContentElement('tab-content', 'title').getValue();
        var textValue = this.getContentElement('tab-content', 'text').getValue();
        var targetValue = this.getContentElement('tab-content', 'target').getValue();

        if (currentLink) { // Updating an existing link
          var self = this;

          ['href', 'title', 'target'].forEach(function (attribute) {
            var value;
            var dialogElement = self.getContentElement('tab-content', attribute);
            if ("undefined" !== typeof dialogElement) {
              value = dialogElement.getValue();
              if (value) {
                currentLink.setAttribute(attribute, value);
              } else {
                currentLink.removeAttribute(attribute);
              }
            } else {
              currentLink.removeAttribute(attribute);
            }
          });

          // We changed the content, so need to select it again.
          selection.selectElement(currentLink);

        } else { // We are creating a new link

          var rangesToSelect = [];
          var style = new CKEDITOR.style({
            element: 'a',
            attributes: {
              href: hrefValue,
              title: titleValue,
              target: targetValue
            }
          });
          style.type = CKEDITOR.STYLE_INLINE; // need to override... dunno why.

          var ranges = selection.getRanges();
          for (var i = 0; i < ranges.length; i++) {
            var range = ranges[i];

            if (range.collapsed) { // Use link URL as text with a collapsed cursor.
              var text = new CKEDITOR.dom.text(textValue);
              range.insertNode(text);
              range.selectNodeContents(text);
            } else {
              // Editable links nested within current range should be removed, so that the link is applied to whole selection.
              /*
              var nestedLinks = range._find('a');
              for (var j = 0; j < nestedLinks.length; j++) {
                nestedLinks[j].remove(true);
              }
               */
            }

            // Apply style.
            style.applyToRange(range, editor);
            rangesToSelect.push(range);
          }

          editor.getSelection().selectRanges(rangesToSelect);
        }
      },

      // This is fired on closing the dialog.
      onCancel: function () {
        var hrefElement = this.getContentElement('tab-content', 'href');
        ULink.selector.close(hrefElement.getInputElement().$);
      }
    };
  });

}(CKEDITOR, Drupal));
