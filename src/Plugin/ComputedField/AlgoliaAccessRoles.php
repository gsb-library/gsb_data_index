<?php

namespace Drupal\gsb_data_index\Plugin\ComputedField;

use Drupal\computed_field\Attribute\ComputedField;
use Drupal\computed_field\Field\ComputedFieldDefinitionWithValuePluginInterface;
use Drupal\computed_field\Plugin\ComputedField\ComputedFieldBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\UserSession;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Computed field listing role IDs granted "view" access to this node.
 *
 * This is intended to be indexed into Algolia (via Search API Algolia) as
 * a multi-value string attribute, e.g. "access_roles". At query time, a
 * secured API key's `filters` should restrict results to
 * `access_roles:<one of the current user's roles>` OR
 * `access_uids:<current uid>` (see AlgoliaAccessUids and
 * AlgoliaSecuredKeyGenerator).
 *
 * IMPORTANT: this walks every role and asks Drupal's node access system
 * (which includes Nodeaccess's hook_node_grants()/hook_node_access())
 * whether a user holding *only* that role could view the node. That means
 * it reflects whatever Nodeaccess (or any other node access module) has
 * actually granted for this specific node, rather than hard-coding
 * Nodeaccess's internal realm/gid scheme.
 *
 * Caveat: this issues one access check per role per node. For large role
 * counts and bulk re-indexing this can be slow; consider batching or
 * caching if that becomes a problem.
 */
#[ComputedField(
  id: 'algolia_access_roles',
  label: new TranslatableMarkup('Algolia access roles'),
  field_type: 'string',
)]
class AlgoliaAccessRoles extends ComputedFieldBase {

  /**
   * {@inheritdoc}
   */
  public function computeValue(EntityInterface $host_entity, ComputedFieldDefinitionWithValuePluginInterface $computed_field_definition): array {
    if ($host_entity->getEntityTypeId() !== 'node') {
      return [];
    }

    /** @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface $access_handler */
    $access_handler = \Drupal::entityTypeManager()->getAccessControlHandler('node');
    /** @var \Drupal\user\RoleStorageInterface $role_storage */
    $role_storage = \Drupal::entityTypeManager()->getStorage('user_role');

    $granted = [];

    foreach (array_keys($role_storage->loadMultiple()) as $rid) {
      // Build a throwaway account holding only this role, so we can record
      // exactly which single role unlocks the node. 'anonymous' needs uid 0;
      // every other role (including 'authenticated') needs a positive uid
      // or Drupal won't treat the session as logged in.
      $account = new UserSession([
        'uid' => $rid === 'anonymous' ? 0 : 1,
        'roles' => [$rid],
      ]);

      // Reset the static access cache between checks; the access control
      // handler and hook implementations may cache per-account/per-node.
      $access_handler->resetCache([$host_entity->id()]);

      if ($access_handler->access($host_entity, 'view', $account)) {
        $granted[] = $rid;
      }
    }

    return $granted;
  }

}
