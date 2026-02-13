<?php

namespace Drupal\gsb_data_index\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\samlauth\Event\SamlauthEvents;
use Drupal\samlauth\Event\SamlauthUserSyncEvent;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for the gsb_data_index module.
 */
class SAMLRolesEventSubscriber implements EventSubscriberInterface {

  /**
   * The configuration factory service.
   *
   * We're doing $configFactory->get() all over the place to access our
   * configuration, which (despite its convoluted-ness) is actually a little
   * more efficient than storing the config object in a variable in this class.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new SamlauthUsersyncEventSubscriber.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The EntityTypeManager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LoggerInterface $logger, EntityTypeManagerInterface $entity_type_manager) {
    $this->configFactory = $config_factory;
    $this->logger = $logger;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[SamlauthEvents::USER_SYNC][] = ['onUserSync'];
    return $events;
  }

  /**
   * Assigns/unassigns roles as needed during user sync.
   *
   * @param \Drupal\samlauth\Event\SamlauthUserSyncEvent $event
   *   The event being dispatched.
   */
  public function onUserSync(SamlauthUserSyncEvent $event) {

    /** @var \Drupal\user\Entity\Role[] $valid_roles */
    $valid_roles = $this->entityTypeManager->getStorage('user_role')->loadMultiple();
    unset($valid_roles[UserInterface::ANONYMOUS_ROLE]);
    unset($valid_roles[UserInterface::AUTHENTICATED_ROLE]);
    $account = $event->getAccount();
    $attributes = $event->getAttributes();

    $this->logger->debug('Valid roles: @roles', ['@roles' => implode(', ', array_keys($valid_roles))]);
    $this->logger->debug('SAML user sync: attributes: @attributes', ['@attributes' => implode(', ', $attributes)]);
  }
}
