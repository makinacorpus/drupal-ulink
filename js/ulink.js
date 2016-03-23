/*global jQuery, Drupal */

// Allow external code to use the selector.
var ULink = {};

(function ($) {
  "use strict";

  /**
   * Default jQuery dialog options
   */
  var dialogOptions = {
    width: "600px",
    height: "auto",
    open: true,
    modal: true
  };

  /**
   * Better autocomplete callbacks
   */
  var callbacks = {

    /**
     * Build URL for better autocomplete
     */
    constructURL: function (path, search) {
      return path + encodeURIComponent(search);
    },

    /**
     * Select callback
     */
    select: function(result, input) {
      // Filter out invalid results
      if (!result.title) {
        return;
      }
      if (result && result.id && result.type) {
        input.val(result.title);
        var hidden = input.parent().find('.ulink-uri');
        if (hidden.length) {
          hidden.val('entity://' + result.type + '/' + result.id);
        }
      }
    }
  };

  /**
   * Attach to given element
   */
  function attach(input, onSelectCallback) {
    var localCallbacks = {};

    // Be liberal in what we accept
    input = jQuery(input);
    if (!input.length) {
      throw "Cannot attach on nothing";
    }
    if (!input.get(0).type || "text" !== input.get(0).type) {
      input = input.find('input[type=text]:first');
      if (!input.length) {
        throw "Could not find any text input element";
      }
    }

    if (onSelectCallback) {
      localCallbacks.select = onSelectCallback;
    } else {
      localCallbacks.select = callbacks.select;
    }
    localCallbacks.constructURL = callbacks.constructURL;

    input.betterAutocomplete('init', '/ulink/search/', {}, localCallbacks);
  };

  function close(dialog) {
    dialog.find(".content").html("");
    dialog.dialog("close");
    dialog.dialog("destroy");
  };

  /**
   * Enable the global dialog
   */
  function open(onSubmitCallback) {
    var dialog = $('#ulink-dialog');

    // First find content
    $.ajax({
      url: '/ulink/dialog',
      async: true,
      accepts: 'application/json',
      dataType: 'json',
      success: function (data) {

        if (!data.form) {
          return;
        }

        dialog.find(".content").html(data.form);

        var input = dialog.find("input.ulink-value");
        var hidden = dialog.find("input.ulink-uri");
        attach(input);

        dialog.find(".ulink-submit").off("click").on("click", function (event) {
          event.stopPropagation();
          if (input.val()) {
            close(dialog);
            if (onSubmitCallback) {
              onSubmitCallback(input.val(), hidden.val());
            }
          }
          return false;
        });

        // setTimeout() call is a workaround: in some edge cases the dialog
        // opens too quickly and does not center properly according to content
        // size..
        // see http://stackoverflow.com/questions/2231446
        setTimeout(function () {
          dialog.dialog(dialogOptions);
          dialog.show().dialog("open");
        }, 500);
      }
    });
  }

  /**
   * Public API.
   */
  ULink.selector = {
    open: open,
    attach: attach
  };

  /**
   * Drupal behavior, we could actually remove it.
   */
  Drupal.behaviors.ulink = {
    attach: function (context) {
      $(context).find("body").once("ulink", function () {
        $(this).append("<div id=\"ulink-dialog\" style=\"display:none;\"><div class=\"content\"></div></div>");
      });
    }
  };

}(jQuery));
