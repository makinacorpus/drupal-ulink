# µLink

Provide some input filters for making links, along with an optional CKEditor plugin.

# URL schemes and format filter

In two words, you can either write one of:

 *  ```html <a href="entity:node/12">Some entity</a>```
 *  ```html <a href="entity://node/12">Some entity</a>```
 *  ```html <a href="{{node/12}}">Some entity</a>```

in any text that would be ```php check_markup()```'ed and filters will be
processed and turned into correct Drupal URL or URL aliases.

# CKEditor plugin

The CKEditor plugin requires ckeditor >= 4.

# Generating URLs

You may use ``url('entity://node/12')`` to generate the node link, there
is an ``hook_url_outbound_alter`` implementation that will do the job.
