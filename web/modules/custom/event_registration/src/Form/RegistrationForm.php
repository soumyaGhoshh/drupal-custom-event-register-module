<?php

namespace Drupal\event_registration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a public registration form.
 */
class RegistrationForm extends FormBase {

  protected $database;

  public function __construct(Connection $database) {
    $this->database = $database;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  public function getFormId() {
    return 'event_registration_public_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $categories = $this->database->select('event_configuration', 'e')
      ->fields('e', ['category'])
      ->distinct()
      ->execute()
      ->fetchCol();

    $form['category'] = [
      '#type' => 'select',
      '#title' => $this->t('Category of the event'),
      '#options' => array_combine($categories, $categories),
      '#required' => TRUE,
    ];

    $events = $this->database->select('event_configuration', 'e')
      ->fields('e', ['id', 'event_name'])
      ->execute()
      ->fetchAllKeyed(0, 1);

    $form['event_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Event Name'),
      '#options' => $events,
      '#required' => TRUE,
    ];

    $form['details'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Personal Details'),
    ];

    $form['details']['full_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Full Name'),
      '#required' => TRUE,
    ];

    $form['details']['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email Address'),
      '#required' => TRUE,
    ];

    $form['details']['college_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('College Name'),
      '#required' => TRUE,
    ];

    $form['details']['department'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Department'),
      '#required' => TRUE,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit Registration'),
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $event_id = $form_state->getValue('event_id');
    
    $event_info = $this->database->select('event_configuration', 'e')
      ->fields('e')
      ->condition('id', $event_id)
      ->execute()
      ->fetchObject();

    $values = [
      'event_id' => $event_id,
      'name' => $form_state->getValue(['details', 'full_name']),
      'email' => $form_state->getValue(['details', 'email']),
      'college' => $form_state->getValue(['details', 'college_name']),
      'department' => $form_state->getValue(['details', 'department']),
      'category' => $event_info->category,
      'event_date' => $event_info->event_date,
      'event_name' => $event_info->event_name,
      'created' => time(),
    ];

    $this->database->insert('event_registration')
      ->fields($values)
      ->execute();

    $this->messenger()->addStatus($this->t('Registration successful!'));
  }

}