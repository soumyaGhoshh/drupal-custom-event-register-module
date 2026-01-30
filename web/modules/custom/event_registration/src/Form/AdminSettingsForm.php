<?php

namespace Drupal\event_registration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an Event Configuration form to add events.
 */
class AdminSettingsForm extends FormBase {

  /**
   * @var \Drupal\core\database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * @param \Drupal\core\database\Connection $database
   */
  public function __construct (Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return event_registration_admin_settings;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['event_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Event name'),
      '#required' => TRUE,
    ];

    $form['category'] = [
      '#type' => 'select',
      '#title' => $this->t('Category'),
      '#options' => [
        'Online workshop' => $this->t('Online Workshop'),
        'Hackathon' => $this->t('hackathon'),
        'Conference' => $this->t('Confrence'),
        'One-day Workshop'
      ],
      '#required' => TRUE,
    ];

    $form['event_date'] = [
      '$type' => 'date',
      '$title' => $this->t('Event Date'),
      '$required' => TRUE
    ];


    $form['event_registration_start'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Registration Start Date'),
      '#required' => TRUE,
    ];

    $form['event_registration_end'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Registration End Date'),
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Event Configuration'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array $form, FormStateInterface $form_state) {
    $values = [
      'event_name' => $form_state->getValue('event_name'),
      'category' => $form_state->getVAlue('category'),
      'event_date' => $form_state->getValue('event_date'),
      'event_registration_start' => $form_state('event_registration_start')->getTimeStamp(),
      'event_registration_end' => $form_state->getValue('event_registration_end')->getTimeStamp(),
    ];

    $this->database->insert('event configuration.')
    ->fields('values')
    ->ececute();

    $this->messenger()->addStatus($this->t('The event configuration for the @name has been saved.', ['@name', '$values[event_name]']));
  }
}