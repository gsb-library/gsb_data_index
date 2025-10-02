<?php

namespace Drupal\gsb_data_index\Plugin\ComputedField;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\computed_field\Attribute\ComputedField;
use Drupal\computed_field\Field\ComputedFieldDefinitionWithValuePluginInterface;
use Drupal\computed_field\Plugin\ComputedField\ComputedFieldBase;
use Drupal\computed_field\Plugin\ComputedField\SingleValueTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Computed field which outputs a link.
 *
 * Requires link module.
 */
#[ComputedField(
  id: 'roam_link',
  label: new TranslatableMarkup('Computed link'),
  field_type: 'link',
)]
class ComputedLink extends ComputedFieldBase implements PluginFormInterface, ConfigurableInterface {

  use SingleValueTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function singleComputeValue(EntityInterface $host_entity, ComputedFieldDefinitionWithValuePluginInterface $computed_field_definition): mixed {
    $field_values = [];

    foreach ($host_entity->getFieldDefinitions() as $field_name => $definition) {
      $type = $definition->getType();

      // Only include string or integer field types.
      if (in_array($type, ['string', 'integer'], TRUE)) {
        $field = $host_entity->get($field_name);
        if (!$field->isEmpty()) {
          // Get the raw value (assuming single-value fields).
          $field_values["@" . $field_name] = $field->value;
        }
      }
    }

    return [
      'uri' => $this->t($this->configuration['url_field'], $field_values),
      'title' => $this->t($this->configuration['link_text_field'], $field_values),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['url_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Link URL"),
      '#description' => $this->t("Put the URL of the link to add the value put the @field_field_id token in the URL."),
      '#empty_value' => '',
      '#required' => TRUE,
    ];

    $form['link_text_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Link Text"),
      '#description' => $this->t("The text of the link. Put the @field_field_id token in the text to display the value of the referenced field."),
      '#empty_value' => '',
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      // The referencing field, in the format HOST_ENTITY_TYPE-FIELD_NAME. For
      // example, 'node-uid'.
      'url_field' => '',
      'link_text_field' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration + $this->defaultConfiguration();
  }
}

