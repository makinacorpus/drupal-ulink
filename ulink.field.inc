<?php
/**
 * @file
 * Field handling related functions.
 *
 * I have to admin, some of the pieces of code in there have been taken from
 * the URL module; all credits to its authors.
 */

/**
 * Implements hook_field_info().
 */
function ulink_field_info() {
  return [
    'ulink' => [
      'label'             => t("Link"),
      'description'       => t("Stores either external URLs, or internal entity URLs"),
      'instance_settings' => [],
      'default_widget'    => 'ulink',
      'default_formatter' => 'ulink',
    ],
  ];
}

/**
 * Implements hook_field_settings_form().
 */
function ulink_field_settings_form($field, $instance, $has_data) {
  return [];
}

/**
 * Implements hook_field_widget_info().
 */
function ulink_field_formatter_info() {
  return [
    'ulink' => [
      'label'       => t("Link display"),
      'field types' => ['ulink'],
      'settings'    => ['trim_length' => 80, 'nofollow' => false],
    ],
  ];
}

/**
 * Implements hook_field_formatter_settings_form().
 */
function ulink_field_formatter_settings_form($field, $instance, $view_mode, $form, &$form_state) {
  $element  = [];

  $display  = $instance['display'][$view_mode];
  $settings = $display['settings'];

  $element['trim_length'] = [
    '#type'             => 'textfield',
    '#title'            => t('Trim the link text to certain number of characters'),
    '#description'      => t('To leave long link text alone, leave this blank.'),
    '#default_value'    => $settings['trim_length'],
    '#size'             => 10,
    '#element_validate' => ['element_validate_integer_positive'],
  ];

  $element['nofollow'] = [
    '#type'             => 'checkbox',
    '#title'            => t('Add rel="nofollow" to external links (internal links will remain unchanged)'),
    '#default_value'    => $settings['nofollow'],
  ];

  return $element;
}

/**
 * Implements hook_field_formatter_settings_summary().
 */
function ulink_field_formatter_settings_summary($field, $instance, $view_mode) {
  return "&hellip"; // Must not be empty.
}

/**
 * Implements hook_field_formatter_view().
 */
function ulink_field_formatter_view($entity_type, $entity, $field, $instance, $langcode, $items, $display) {
  $element  = [];

  if ($items) {
    $settings = $display['settings'];

    foreach ($items as $delta => $item) {
      $title = empty($item['title']) ? $item['value'] : $item['title'];

      $generated = ulink_get_path($item['value']);
      if ($generated) {
        $item['path'] = $generated;
        // @todo for internal links, we must be able to load the entity to
        //   have a title; that's for a future version
        if (!$title) {

        }
      } else {
        $parsed = drupal_parse_url($item['value']);
        $item['path'] = $parsed['path'];
        $item['options']['query'] = $parsed['query'];
        $item['options']['fragment'] = $parsed['fragment'];
      }

      $item['options']['attributes'] = $item['attributes'] ? $item['attributes'] : [];

      // Trim the link title to the desired length.
      if (!empty($settings['trim_length'])) {
        $title = truncate_utf8($title, $settings['trim_length'], false, true);
      }

      $element[$delta]['#markup'] = l($title, $item['path'], $item['options']);
    }
  }

  return $element;
}

/**
 * Implements hook_field_widget_info().
 */
function ulink_field_widget_info() {
  return [
    'ulink' => [
      'label'       => t("Link"),
      'field types' => ['ulink'],
      'settings'    => [
        'autocomplete' => FALSE,
        // TODO
        // 'allow_external' => FALSE,
        // 'allowed_bundles'  => [],
      ],
    ],
  ];
}

/**
 * Implements hook_field_widget_settings_form().
 */
function ulink_field_widget_settings_form($field, $instance) {
  $widget = $instance['widget'];
  $settings = $widget['settings'];

  $form['autocomplete'] = [
    '#title'         => t('Autocomplete internal links'),
    '#type'          => 'checkbox',
    '#default_value' => !empty($settings['autocomplete']),
  ];

  // TODO
  //  $form['allow_external'] = [
  //    '#title' => t('Allow external links'),
  //    '#type' => 'checkbox',
  //    '#default_value' => $settings['allow_external'],
  //  ];
  //
  //  $form['allowed_bundles'] = [
  //    '#title' => t('Allowed content types'),
  //    '#type' => 'checkboxes',
  //    '#options' => node_type_get_names(),
  //    '#default_value' => $settings['allowed_bundles'],
  //  ];

  return $form;
}

/**
 * Implements hook_field_widget_form().
 */
function ulink_field_widget_form(&$form, &$form_state, $field, $instance, $langcode, $items, $delta, $element) {
  $widget = $instance['widget'];
  $settings = $widget['settings'];

  $element['value'] = [
    '#type'             => 'textfield',
    '#title'            => t('URL'),
    '#default_value'    => isset($items[$delta]['value']) ? $items[$delta]['value'] : '',
    '#maxlength'        => 2048,
    '#required'         => !empty($element['#required']),
    '#element_validate' => ['ulink_validate_url'],
  ];

  if (!empty($settings['autocomplete'])) {
    $element['value']['#attached']['library'][] = ['ulink', 'dialog'];
    $element['value']['#attributes']['class'][] = 'ulink-autocomplete';
  }

  if (!module_exists('elements')) {
    $element['value']['#type'] = 'textfield';
  }

  $element['title'] = [
    '#type'             => 'textfield',
    '#title'            => t('Title'),
    '#default_value'    => isset($items[$delta]['title']) ? $items[$delta]['title'] : '',
    '#maxlength'        => 1024,
    '#weight'           => 10,
    '#description'      => t("If you leave it empty, internal links will have the target entity title"),
  ];

  $element['attributes'] = [
    '#type'             => 'value',
    '#value'            => !empty($items[$delta]['attributes']) ? $items[$delta]['attributes'] : [],
  ];

  // If the widget is being used on a default value form, due to a core bug we
  // need to set the attributes default value to be an already serialized
  // value so that it saves properly.
  // @todo Remove when http://drupal.org/node/1899498 is fixed.
  if (empty($element['#entity']) && !isset($items[$delta]['attributes'])) {
    $element['attributes']['#value'] = null;
  }

  return $element;
}

/**
 * Implements hook_field_attach_presave().
 *
 * We use this hook instead of hook_field_presave() because it gets executed
 * just before the real save, and allows us to avoid potential conflicts with
 * other modules.
 *
 * @see ulink_field_load()
 */
function ulink_field_attach_presave($entity_type, $entity) {
  list(,, $bundle) = entity_extract_ids($entity_type, $entity);
  foreach (field_info_instances($entity_type, $bundle) as $instance) {
    $field_name = $instance['field_name'];

    $field = field_info_field($field_name);
    if (!$field || 'ulink' !== $field['type']) {
      continue;
    }

    // Field API magic: foreach-ception.
    if (!empty($entity->{$field_name})) {
      foreach ($entity->{$field_name} as $language => $items) {
        foreach ($items as $delta => $item) {
          if ($item['attributes']) {
            $entity->{$field_name}[$language][$delta]['attributes'] = serialize($item['attributes']);
          } else {
            $entity->{$field_name}[$language][$delta]['attributes'] = null;
          }
        }
      }
    }
  }
}

/**
 * Implements hook_field_load().
 *
 * @see ulink_field_attach_presave()
 */
function ulink_field_load($entity_type, $entities, $field, $instances, $langcode, &$items, $age) {
  if ($items) {
    foreach ($items as $id => $entityItems) {
      foreach ($entityItems as $delta => $item) {
        if ($item['attributes']) {
          $items[$id][$delta]['attributes'] = unserialize($item['attributes']);
        } else {
          $item['attributes'] = [];
        }
      }
    }
  }
}

/**
 * Implements hook_field_is_empty().
 */
function ulink_field_is_empty($item, $field) {
  return empty($item['value']);
}

/**
 * Validate URL.
 */
function ulink_validate_url(&$element, &$form_state) {

  $value = trim($element['#value']);
  form_set_value($element, $value, $form_state);

  if (empty($value)) {
    return; // '#size' and '#required' are already validated by the form API.
  }

  // Attempt to generate internal links.
  $generated = ulink_get_path($value);
  if ($generated) {
    return; // This is a valid internal link; this passes.
  }

  // Else fallback on Drupal URL validation.
  if (!valid_url($value, true)) {
    form_error($element, t('The URL %url is not valid.', ['%url' => $value]));
  }
}
