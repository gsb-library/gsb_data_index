<?php

namespace Drupal\gsb_data_index\Plugin\Action;

use Drupal\node\Entity\Node;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * GSB Data Index Sync To Data Warehouse Action.
 *
 * @Action(
 *   id = "gsb_data_index_sync_to_data_warehouse_action",
 *   label = @Translation("Sync To data warehouse"),
 *   type = "node"
 * )
 */

class SyncToDataWarehouseAction extends ViewsBulkOperationsActionBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function execute(ContentEntityInterface $entity = NULL) {

    if ($entity->bundle() == 'resource') {
      gsb_data_index_update_dw_records($entity, false);

      return $this->t(':title has been synced to the data warehouse.',
        [
          ':title' => $entity->getTitle(),
        ]
      );
    }
    else {
      return $this->t(':title is not a resource.',
        [
          ':title' => $entity->getTitle(),
        ]
      );
    }

  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return TRUE;
    if ($object instanceof Node) {
      $can_update = $object->access('update', $account, TRUE);
      $can_edit = $object->access('edit', $account, TRUE);

      return $can_edit->andIf($can_update)->isAllowed();
    }

    return FALSE;
  }
}