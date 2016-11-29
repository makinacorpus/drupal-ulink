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
        var element = plugin.getSelectedLink(editor);
        if (element && element.hasAttribute('href')) {
          // Don't change selection if some element is already selected.
          // For example - don't destroy fake selection.
          if (!selection.getSelectedElement()) {
            selection.selectElement(element);
          }
        } else {
          element = null;
        }

        // Fill in values from the current selection
        var hrefElement = this.getContentElement('tab-content', 'href');
        var textElement = this.getContentElement('tab-content', 'text');
        var titleElement = this.getContentElement('tab-content', 'title');
        var targetElement = this.getContentElement('tab-content', 'target');
        if (element) {
          hrefElement.setValue(element.getAttribute('href'));
          textElement.setValue(element.getHtml());
          titleElement.setValue(element.getAttribute('title'));
          targetElement.setValue(element.getAttribute('target'));
        } else {
          textElement.setValue(selection.getSelectedText());
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

        var selection = editor.getSelection();
        var element = plugin.getSelectedLink(editor);
        if (element && element.hasAttribute('href')) {
          // Don't change selection if some element is already selected.
          // For example - don't destroy fake selection.
          if (!selection.getSelectedElement()) {
            selection.selectElement(element);
          }
        } else {
          element = null;
        }

        var hrefElement = this.getContentElement('tab-content', 'href');
        var textElement = this.getContentElement('tab-content', 'text');
        var titleElement = this.getContentElement('tab-content', 'title');
        var targetElement = this.getContentElement('tab-content', 'target');

        if (!element) {

          // Creating a new element
          var range = selection.getRanges()[0];
          var text = new CKEDITOR.dom.text(textElement.getValue());

          // Use link URL as text with a collapsed cursor
          if (range.collapsed) {
            range.insertNode(text);
            range.selectNodeContents(text);
          }

          var style = new CKEDITOR.style({
            element: 'a',
            attributes: {
              href: hrefElement.getValue(),
              title: titleElement.getValue(),
              target: targetElement.getValue()
            }
          });

          style.type = CKEDITOR.STYLE_INLINE; // need to override... dunno why.
          style.applyToRange(range, editor);
          range.select();

        } else {

          // Updating an existing one
          var self = this;

          ['href', 'title', 'target'].forEach(function (attribute) {
            var value;
            var dialogElement = self.getContentElement('tab-content', attribute);
            if ("undefined" !== typeof dialogElement) {
              value = dialogElement.getValue();
              if (value) {
                element.setAttribute(attribute, value);
              } else {
                element.removeAttribute(attribute);
              }
            } else {
              element.removeAttribute(attribute);
            }
          });

          textElement = this.getContentElement('tab-content', 'text');
          element.setHtml(textElement.getValue());

          // We changed the content, so need to select it again.
          selection.selectElement(element);
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
