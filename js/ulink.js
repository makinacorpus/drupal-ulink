/*global jQuery, Drupal */

// Allow external code to use the selector.
// window hack is to make it work within webpack.
window.ULink = {};
var ULink = window.ULink;

(function ($) {
  "use strict";

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
        // Do not replace value if there is already one (link edit)
        if (!input.val()) {
          input.val(result.title);
        }
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
  }

  function close(input) {
    // We just need to destroy the autocomplete and the jQuery dialog
    jQuery(input).betterAutocomplete('destroy');
    // This ensure the dialog actually disappear
    return true;
  }

  /**
   * Public API.
   */
  ULink.selector = {
    attach: attach,
    close: close
  };

  /**
   * Drupal behavior, we could actually remove it.
   */
  Drupal.behaviors.ulink = {
    attach: function (context) {
      // Activate on autocomplete fields
      $('input.ulink-autocomplete', context).each(function() {
        var input = this;
        var title = $('[name="' + $(input).attr('name').replace('value', 'title') + '"]');
        ULink.selector.attach(input, function (result) {
          if (!result || !result.title) { // Filter out invalid results
            return;
          }
          if (result.id && result.type) {
            // Do not override previous user input
            if (title && !title.val()) {
              // Convert HTML entities
              title.val($("<div/>").html(result.title).text());
            }
            $(input).val('entity:' + result.type + '/' + result.id);
          }
        });
      });
    }
  };

}(jQuery));
