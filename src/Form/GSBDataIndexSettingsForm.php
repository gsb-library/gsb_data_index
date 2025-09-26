<?php
namespace Drupal\gsb_data_index\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class GSBDataIndexSettingsForm extends ConfigFormBase {

  /**
  * {@inheritdoc}
  */
  protected function getEditableConfigNames() {
    return ['gsb_data_index.settings'];
  }

  /**
  * {@inheritdoc}
  */
  public function getFormId() {
    return 'gsb_data_index_settings_form';
  }

  /**
  * Build the configuration form.
  */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('gsb_data_index.settings');

    $form['snaplogic_environment'] = [
      '#type' => 'select',
      '#title' => $this->t('SnapLogic Environment'),
      '#description' => $this->t('The environment to use for SnapLogic.'),
      '#options' => [
        'Disabled' => 'Disable',
        'StanfordDev' => 'Development',
        'StanfordUAT' => 'UAT',
        'StanfordProd' => 'Production',
      ],
      '#default_value' => $config->get('snaplogic_environment'),
      '#required' => FALSE,
    ];

    $form['snaplogic_api_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('SnapLogic API Token'),
      '#description' => $this->t('Enter your SnapLogic API token.'),
      '#default_value' => $config->get('snaplogic_api_token'),
      '#required' => FALSE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
  * Submit form handler.
  */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('gsb_data_index.settings')
    ->set('snaplogic_environment', $form_state->getValue('snaplogic_environment'))
    ->set('snaplogic_api_token', $form_state->getValue('snaplogic_api_token'))
    ->save();
    parent::submitForm($form, $form_state);
  }
}