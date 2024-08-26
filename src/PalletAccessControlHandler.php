<?php

namespace Drupal\neo_color;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\neo_build\Build;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the access control handler for the taxonomy vocabulary entity type.
 *
 * @see \Drupal\taxonomy\Entity\Vocabulary
 */
final class PalletAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * The build service.
   *
   * @var \Drupal\neo_build\Build
   */
  protected $build;

  /**
   * PalletAccessControlHandler constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param \Drupal\neo_build\Build $build
   *   The build service.
   */
  public function __construct(EntityTypeInterface $entity_type, Build $build) {
    parent::__construct($entity_type);
    $this->build = $build;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('neo_build')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($operation === 'delete' && in_array($entity->id(), PalletInterface::PROTECTED)) {
      return AccessResult::forbidden();
    }
    return parent::checkAccess($entity, $operation, $account);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    if (!$this->build->isDevMode()) {
      return AccessResult::forbidden('Pallets can only be created while in DEV mode.');
    }
    return parent::checkCreateAccess($account, $context, $entity_bundle);
  }

}
