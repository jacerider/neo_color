<?php

namespace Drupal\neo_color\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsButtonsWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\neo_tooltip\Tooltip;
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
final class SchemeWidget extends OptionsButtonsWidget {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected RendererInterface $renderer;

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
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
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
      $container->get('entity_type.manager'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['#style'] = 'inline_elements';
    return $element;
  }

  /**
   * Returns the array of options for the widget.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity for which to return options.
   *
   * @return array
   *   The array of options for the widget.
   */
  protected function getOptions(FieldableEntityInterface $entity) {
    if (!isset($this->options)) {
      $schemes = array_intersect_key($this->getSchemes(), parent::getOptions($entity));
      $options = [];
      foreach ($schemes as $scheme) {
        $options[$scheme->id()] = $scheme->label();
        $build = [
          '#theme' => 'neo_scheme_swatch',
          '#neo_scheme' => $scheme,
        ];
        $tooltip = new Tooltip($scheme->label());
        $tooltip->setPlacementToBottom();
        $tooltip->setTriggerToNearestFocusableElement();
        $tooltip->applyTo($build);
        $options[$scheme->id()] = $this->renderer->render($build);
      }
      $this->options = $options;
    }
    return $this->options;
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
