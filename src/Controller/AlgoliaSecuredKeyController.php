<?php

namespace Drupal\gsb_data_index\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\gsb_data_index\AlgoliaSecuredKeyGenerator;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Returns a per-user Algolia Secured API Key for the resource search UI.
 */
class AlgoliaSecuredKeyController extends ControllerBase {

  public function __construct(
    protected AlgoliaSecuredKeyGenerator $keyGenerator,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(\Symfony\Component\DependencyInjection\ContainerInterface $container) {
    return new static($container->get('gsb_data_index.algolia_secured_key_generator'));
  }

  /**
   * Returns {key, appId, validUntil} as JSON for the current user.
   */
  public function getKey(): JsonResponse {
    try {
      $data = $this->keyGenerator->generateForAccount($this->currentUser());
      // Never cache this response — it's specific to the requesting user
      // and short-lived by design.
      $response = new JsonResponse($data);
      $response->setPrivate();
      $response->setMaxAge(0);
      return $response;
    }
    catch (\RuntimeException $e) {
      $this->getLogger('gsb_data_index')->error($e->getMessage());
      return new JsonResponse(['error' => 'Search is temporarily unavailable.'], 500);
    }
  }

}
