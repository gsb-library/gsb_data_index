<?php

namespace Drupal\gsb_data_index\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * Sync node to Data Warehouse.
 *
 * @Action(
 *   id = "gsb_data_index_sync_to_data_warehouse_node_action",
 *   label = @Translation("Sync to data warehouse"),
 *   type = "node",
 *   confirm = TRUE,
 * )
 */
class SyncToDataWarehouseAction extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if ($entity instanceof NodeInterface) {
      if ($entity->bundle() === 'resource') {
        // Call your custom sync function.
        gsb_data_index_update_dw_records($entity, FALSE);

        \Drupal::messenger()->addMessage($this->t('@title has been synced to the data warehouse.', [
          '@title' => $entity->label(),
        ]));
      }
      else {
        \Drupal::messenger()->addWarning($this->t('@title is not a resource.', [
          '@title' => $entity->label(),
        ]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    // Allow users with update access on the node to run this action.
    return $object->access('update', $account, $return_as_object);
  }

}