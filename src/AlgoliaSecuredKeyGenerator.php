<?php

namespace Drupal\gsb_data_index;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Algolia\AlgoliaSearch\Api\SearchClient;

/**
 * Generates per-user Algolia Secured API Keys for the resource index.
 *
 * The key bakes in two independent restrictions, mirroring the two access
 * layers on the site:
 *
 * - Node-level (Nodeaccess): a `filters` clause limiting results to
 *   records whose access_roles/access_uids match the current user. This
 *   is enforced server-side by Algolia; the client-side JS cannot alter
 *   or remove it.
 * - Field-level (the 'allowed_roles' third-party setting used by
 *   gsb_data_index_entity_field_access()): `restrictSearchableAttributes`
 *   and `attributesToRetrieve`, so a field a role can't see in Drupal is
 *   also never searchable or returned to that role in Algolia results.
 *
 * Always-visible base attributes (title, objectID, etc.) should be listed
 * in ALWAYS_VISIBLE_ATTRIBUTES below, or added dynamically by inspecting
 * your Search API index config.
 */
class AlgoliaSecuredKeyGenerator {

  /**
   * Attributes that are never field-permission-restricted.
   *
   * Adjust this list to match your actual Search API Algolia index
   * field IDs (they may differ from the Drupal field machine names if
   * you renamed them in the index configuration).
   */
  const ALWAYS_VISIBLE_ATTRIBUTES = ['objectID', 'title', 'body', 'field_topics'];

  /**
   * How long a generated key remains valid, in seconds.
   */
  const KEY_TTL = 3600;

  public function __construct(
    protected ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Builds a Secured API Key scoped to the given account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user the key is being generated for.
   * @param string $bundle
   *   The content type whose field-visibility rules should apply.
   *
   * @return array{key: string, appId: string, validUntil: int}
   *   The secured key and the app ID needed to initialize the Algolia
   *   client on the frontend.
   *
   * @throws \RuntimeException
   *   If Algolia credentials aren't configured.
   */
  public function generateForAccount(AccountInterface $account, string $bundle = 'resource'): array {
    $config = $this->configFactory->get('gsb_data_index.settings');
    $app_id = $config->get('algolia_app_id');
    $search_key = $config->get('algolia_search_api_key');

    if (empty($app_id) || empty($search_key)) {
      throw new \RuntimeException('Algolia app ID or search API key is not configured at /admin/config/gsb-data-index.');
    }

    $filters = $this->buildAccessFilter($account);
    $visible_attributes = gsb_data_index_get_visible_field_names($account, 'node', $bundle);
    $attributes = array_unique(array_merge(self::ALWAYS_VISIBLE_ATTRIBUTES, $visible_attributes));

    $restrictions = [
      'filters' => $filters,
      'restrictSearchableAttributes' => $attributes,
      'attributesToRetrieve' => $attributes,
      'validUntil' => \Drupal::time()->getRequestTime() + self::KEY_TTL,
    ];

    // Support both v4 (typed restrictions object) and v3 (plain array)
    // of the Algolia PHP client, since we can't be sure which is pulled
    // in transitively by search_api_algolia on any given site.
    $restrictions_class = '\Algolia\AlgoliaSearch\Models\SecuredApiKeyRestrictions';
    if (class_exists($restrictions_class)) {
      $key = SearchClient::generateSecuredApiKey($search_key, new $restrictions_class($restrictions));
    }
    else {
      $key = SearchClient::generateSecuredApiKey($search_key, $restrictions);
    }

    return [
      'key' => $key,
      'appId' => $app_id,
      'validUntil' => $restrictions['validUntil'],
    ];
  }

  /**
   * Builds the `filters` string restricting results to this account.
   */
  protected function buildAccessFilter(AccountInterface $account): string {
    $clauses = [];

    foreach ($account->getRoles() as $rid) {
      $clauses[] = sprintf('access_roles:%s', $this->escapeFilterValue($rid));
    }

    if ($account->isAuthenticated()) {
      $clauses[] = sprintf('access_uids:%d', $account->id());
    }

    // No roles at all shouldn't be possible (anonymous always has at
    // least the 'anonymous' role), but fail closed just in case: a filter
    // that matches nothing is much safer than an empty filters string,
    // which Algolia would treat as "no restriction".
    if (empty($clauses)) {
      return 'access_roles:__no_access__';
    }

    return implode(' OR ', $clauses);
  }

  /**
   * Escapes a value for safe inclusion in an Algolia filter string.
   */
  protected function escapeFilterValue(string $value): string {
    return str_replace(['"', "'"], '', $value);
  }

}
