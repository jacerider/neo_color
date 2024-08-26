<?php

namespace Drupal\neo_color\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the 'neo_scheme' entity field type.
 *
 * @FieldType(
 *   id = "neo_scheme",
 *   label = @Translation("Color Scheme"),
 *   description = @Translation("Color scheme selection."),
 *   default_widget = "neo_scheme",
 *   default_formatter = "entity_reference_entity_id",
 *   list_class = "\Drupal\Core\Field\EntityReferenceFieldItemList",
 * )
 */
class SchemeItem extends EntityReferenceItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'target_type' => 'neo_scheme',
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = [];
    return $element;
  }

}
