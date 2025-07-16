<?php
namespace Drupal\gsb_data_index\EventSubscriber;

use Drupal\feeds\Event\EntityEvent;
use Drupal\feeds\Event\FeedsEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to Feeds entity presave events.
 */
class FeedsPresaveSubscriber implements EventSubscriberInterface {

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        $events = [];
        $events[FeedsEvents::PROCESS_ENTITY_PRESAVE][] = 'presave';
        return $events;
    }

    /**
     * React on Feeds entity presave.
     */
    public function presave(EntityEvent $event) {
        $feed_type_id = $event->getFeed()->getType()->id();
        $entity = $event->getEntity();
        $item = $event->getItem();

        if ($feed_type_id !== 'import_datasets') {
            return;
        }

        \Drupal::logger('gsb_data_index')->notice('Bundle: @id', [
            '@id' => $entity->bundle(),
        ]);

        if ($entity->bundle() !== 'resource') {
            return;
        }

        $abstract = $item->get('abstract');

        if (!empty($abstract)) {
            $version = \Drupal::entityTypeManager()
                ->getStorage('versions')
                ->create([
                    'type' => 'version',
                    'field_v_abstract' => [
                        'value' => $abstract,
                        'format' => 'full_html',
                    ],
                ]);
            $version->save();

            $entity->get('field_versions')->appendItem([
                'target_id' => $version->id(),
            ]);

            \Drupal::logger('gsb_data_index')->notice('Created and attached version entity: @id', [
                '@id' => $version->id(),
            ]);
        }
    }
}
