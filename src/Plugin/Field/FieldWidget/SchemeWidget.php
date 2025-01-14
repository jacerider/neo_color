<?php

namespace Drupal\neo_color\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'neo_scheme' widget.
 *
 * @FieldWidget(
 *   id = "neo_scheme",
 *   label = @Translation("Scheme Select"),
 *   field_types = {
 *     "neo_scheme"
 *   },
 *   multiple_values = TRUE
 * )
 */
final class SchemeWidget extends WidgetBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Abstract over the actual field columns.
   *
   * Allows different field types to reuse those widgets.
   *
   * @var string
   */
  protected $column;

  /**
   * The schemes.
   *
   * @var \Drupal\neo_color\SchemeInterface[]
   */
  protected array $schemes;

  /**
   * Constructs a WidgetBase object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->entityTypeManager = $entity_type_manager;
    $property_names = $this->fieldDefinition->getFieldStorageDefinition()->getPropertyNames();
    $this->column = $property_names[0];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      // Allowm selection of schemes that are dark enabled.
      'allow_dark' => TRUE,
      // Allowm selection of schemes that are color enabled.
      'allow_color' => TRUE,
      // Includes schemes by id.
      'include' => [],
      // Excludes schemes by id.
      'exclude' => [],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['allow_dark'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow Dark Schemes'),
      '#default_value' => $this->getSetting('allow_dark'),
    ];
    $element['allow_color'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow Colored Schemes'),
      '#default_value' => $this->getSetting('allow_color'),
    ];
    $element['include'] = [
      '#type' => 'neo_scheme',
      '#title' => $this->t('Include Schemes'),
      '#multiple' => TRUE,
      '#default_value' => $this->getSetting('include'),
    ];
    $element['exclude'] = [
      '#type' => 'neo_scheme',
      '#title' => $this->t('Exclude Schemes'),
      '#multiple' => TRUE,
      '#default_value' => $this->getSetting('exclude'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Allow dark: @value', ['@value' => $this->getSetting('allow_dark') ? 'Yes' : 'No']);
    $summary[] = $this->t('Allow color: @value', ['@value' => $this->getSetting('allow_color') ? 'Yes' : 'No']);
    if ($include = $this->getSetting('include')) {
      $include = array_map(function ($id) {
        return $this->getSchemes()[$id]->label();
      }, $include);
      $summary[] = $this->t('Include schemes: @schemes', ['@schemes' => implode(', ', $include)]);
    }
    if ($exclude = $this->getSetting('exclude')) {
      $exclude = array_map(function ($id) {
        return $this->getSchemes()[$id]->label();
      }, $exclude);
      $summary[] = $this->t('Include schemes: @schemes', ['@schemes' => implode(', ', $exclude)]);
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['#type'] = 'neo_scheme';
    $element['#show_title'] = FALSE;
    $element['#allow_dark'] = $this->getSetting('allow_dark');
    $element['#allow_color'] = $this->getSetting('allow_color');
    $element['#include'] = $this->getSetting('include');
    $element['#exclude'] = $this->getSetting('exclude');
    $element['#multiple'] = $this->fieldDefinition->getFieldStorageDefinition()->isMultiple();
    $defaultValue = [];
    foreach ($items as $item) {
      $defaultValue[] = $item->target_id;
    }
    $element['#default_value'] = $element['#multiple'] ? $defaultValue : reset($defaultValue);

    // Add our custom validator.
    $element['#element_validate'][] = [static::class, 'validateElement'];
    $element['#key_column'] = $this->column;
    return $element;
  }

  /**
   * Form validation handler for widget elements.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateElement(array $element, FormStateInterface $form_state) {
    if ($element['#required'] && $element['#value'] == '_none') {
      if (isset($element['#required_error'])) {
        $form_state->setError($element, $element['#required_error']);
      }
      else {
        $form_state->setError($element, new TranslatableMarkup('@name field is required.', ['@name' => $element['#title']]));
      }
    }

    // Massage submitted form values.
    // Drupal\Core\Field\WidgetBase::submit() expects values as
    // an array of values keyed by delta first, then by column, while our
    // widgets return the opposite.
    if (is_array($element['#value'])) {
      $values = array_values($element['#value']);
    }
    else {
      $values = [$element['#value']];
    }

    // Filter out the 'none' option. Use a strict comparison, because
    // 0 == 'any string'.
    $index = array_search('_none', $values, TRUE);
    if ($index !== FALSE) {
      unset($values[$index]);
    }

    // Transpose selections from field => delta to delta => field.
    $items = [];
    foreach ($values as $value) {
      $items[] = [$element['#key_column'] => $value];
    }
    $form_state->setValueForElement($element, $items);
  }

  /**
   * Returns the schemes.
   *
   * @return \Drupal\neo_color\SchemeInterface[]
   *   The schemes.
   */
  protected function getSchemes() {
    if (!isset($this->schemes)) {
      $schemes = [];
      $storage = $this->entityTypeManager->getStorage('neo_scheme');
      $query = $storage->getQuery();
      $query->accessCheck(FALSE);
      $query->condition('status', TRUE);
      $query->sort('weight');
      $ids = $query->execute();
      if ($ids) {
        $schemes = $storage->loadMultiple($ids);
      }
      $this->schemes = $schemes;
    }
    return $this->schemes;
  }

}
