<?php

/**
 * @file
 * Contains data alter hooks for views.
 */

/**
 * Implements hook_views_data_alter().
 */
function ewp_institutions_user_views_data_alter(array &$data) {
  $args = [
    '@label' => t('Institution'),
    '@field_name' => 'user_institution',
    '@entity' => t('User'),
  ];

  $data['hei']['reverse__user__user_institution'] = [
    'relationship' => [
      'title' => t('@entity using @field_name', $args),
      'label' => t('@field_name', $args),
      'group' => t('@label', $args),
      'help' => t('Relate each @entity with the @field_name field set to the @label.', $args),
      'id' => 'entity_reverse',
      'base' => 'users_field_data',
      'base field' => 'uid',
      'field_name' => 'user_institution',
      'field table' => 'user__user_institution',
      'field field' => 'user_institution_target_id',
      'join_extra' => [
        [
          'field' => 'deleted',
          'value' => 0,
          'numeric' => TRUE,
        ],
      ],
    ],
  ];
}
