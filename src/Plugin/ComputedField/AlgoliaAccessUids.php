<?php

namespace Drupal\gsb_data_index\Plugin\ComputedField;

use Drupal\computed_field\Attribute\ComputedField;
use Drupal\computed_field\Field\ComputedFieldDefinitionWithValuePluginInterface;
use Drupal\computed_field\Plugin\ComputedField\ComputedFieldBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Computed field listing specific user IDs granted "view" access to a node.
 *
 * Nodeaccess can grant view access to individual users, not just roles.
 * Those grants live in the {node_access} table under realms scoped to
 * users rather than roles, so unlike AlgoliaAccessRoles this can't be
 * derived by testing accounts — it has to be read directly.
 *
 * VERIFY ON YOUR SITE: Nodeaccess has historically used the realms
 * 'nodeaccess_uid' (explicit per-user grants) and 'nodeaccess_author'
 * (grant to the node's author). Confirm these match your installed
 * version before relying on this in production, e.g.:
 *   SELECT DISTINCT realm FROM node_access;
 */
#[ComputedField(
  id: 'algolia_access_uids',
  label: new TranslatableMarkup('Algolia access uids'),
  field_type: 'integer',
)]
class AlgoliaAccessUids extends ComputedFieldBase {

  /**
   * Nodeaccess realms that grant view access to a specific user ID.
   *
   * Double check these against your site's {node_access} table.
   */
  const USER_SCOPED_REALMS = ['nodeaccess_user'];

  /**
   * {@inheritdoc}
   */
  public function computeValue(EntityInterface $host_entity, ComputedFieldDefinitionWithValuePluginInterface $computed_field_definition): array {
    if ($host_entity->getEntityTypeId() !== 'node') {
      return [];
    }

    $uids = \Drupal::database()->select('node_access', 'na')
      ->fields('na', ['gid'])
      ->condition('na.nid', $host_entity->id())
      ->condition('na.realm', self::USER_SCOPED_REALMS, 'IN')
      ->condition('na.grant_view', 1, '>=')
      ->distinct()
      ->execute()
      ->fetchCol();

    return array_values(array_map('intval', $uids));
  }

}
