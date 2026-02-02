<?php

namespace Drupal\event_registration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a public registration form with validation and email confirmation.
 */
class RegistrationForm extends FormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new RegistrationForm.
   */
  public function __construct(Connection $database, ConfigFactoryInterface $config_factory, MailManagerInterface $mail_manager, AccountProxyInterface $current_user) {
    $this->database = $database;
    $this->configFactory = $config_factory;
    $this->mailManager = $mail_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('config.factory'),
      $container->get('plugin.manager.mail'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'event_registration_public_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $now = time();

    // Fetch categories that have open registration windows.
    $categories = $this->database->select('event_configuration', 'e')
      ->fields('e', ['category'])
      ->condition('event_registration_start', $now, '<=')
      ->condition('event_registration_end', $now, '>=')
      ->distinct()
      ->execute()
      ->fetchCol();

    if (empty($categories)) {
      $form['message'] = [
        '#markup' => '<div class="messages messages--warning">' . $this->t('No events are currently open for registration.') . '</div>',
      ];
      return $form;
    }

    $form['#tree'] = TRUE;

    $form['category'] = [
      '#type' => 'select',
      '#title' => $this->t('Category of the event'),
      '#options' => ['' => $this->t('- Select Category -')] + array_combine($categories, $categories),
      '#ajax' => [
        'callback' => '::updateDateOptions',
        'wrapper' => 'edit-date-wrapper',
        'event' => 'change',
      ],
      '#required' => TRUE,
    ];

    $selected_category = $form_state->getValue('category');
    $date_options = ['' => $this->t('- Select Category First -')];

    if ($selected_category) {
      $dates = $this->database->select('event_configuration', 'e')
        ->fields('e', ['event_date'])
        ->condition('category', $selected_category)
        ->condition('event_registration_start', $now, '<=')
        ->condition('event_registration_end', $now, '>=')
        ->distinct()
        ->execute()
        ->fetchCol();
      $date_options = ['' => $this->t('- Select Event Date -')] + array_combine($dates, $dates);
    }

    $form['event_date_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'edit-date-wrapper'],
    ];

    $form['event_date_wrapper']['event_date'] = [
      '#type' => 'select',
      '#title' => $this->t('Event Date'),
      '#options' => $date_options,
      '#disabled' => empty($selected_category),
      '#ajax' => [
        'callback' => '::updateNameOptions',
        'wrapper' => 'edit-name-wrapper',
        'event' => 'change',
      ],
      '#required' => TRUE,
    ];

    $selected_date = $form_state->getValue(['event_date_wrapper', 'event_date']);
    $name_options = ['' => $this->t('- Select Date First -')];

    if ($selected_category && $selected_date) {
      $events = $this->database->select('event_configuration', 'e')
        ->fields('e', ['id', 'event_name'])
        ->condition('category', $selected_category)
        ->condition('event_date', $selected_date)
        ->condition('event_registration_start', $now, '<=')
        ->condition('event_registration_end', $now, '>=')
        ->execute()
        ->fetchAllKeyed(0, 1);
      $name_options = ['' => $this->t('- Select Event Name -')] + $events;
    }

    $form['event_name_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'edit-name-wrapper'],
    ];

    $form['event_name_wrapper']['event_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Event Name'),
      '#options' => $name_options,
      '#disabled' => empty($selected_date),
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

  public function updateDateOptions(array &$form, FormStateInterface $form_state) {
    return $form['event_date_wrapper'];
  }

  public function updateNameOptions(array &$form, FormStateInterface $form_state) {
    return $form['event_name_wrapper'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $email = $form_state->getValue(['details', 'email']);
    $event_date = $form_state->getValue(['event_date_wrapper', 'event_date']);

    // Prevent duplicate registrations (Email + Event Date).
    $duplicate = $this->database->select('event_registration', 'r')
      ->fields('r', ['id'])
      ->condition('email', $email)
      ->condition('event_date', $event_date)
      ->execute()
      ->fetchField();

    if ($duplicate) {
      $form_state->setErrorByName('details][email', $this->t('You have already registered for an event on this date.'));
    }

    // Special characters check.
    $text_fields = [
      'full_name' => $this->t('Full Name'),
      'college_name' => $this->t('College Name'),
      'department' => $this->t('Department')
    ];
    foreach ($text_fields as $key => $label) {
      $value = $form_state->getValue(['details', $key]);
      if (preg_match('/[^a-zA-Z0-9\s.\-,]/', $value)) {
        $form_state->setErrorByName('details][' . $key, $this->t('@label contains invalid characters.', ['@label' => $label]));
      }
    }

    // Validate Event ID.
    $event_id = $form_state->getValue(['event_name_wrapper', 'event_id']);
    if (empty($event_id)) {
      $form_state->setErrorByName('event_name_wrapper][event_id', $this->t('Please select an event.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $event_id = $form_state->getValue(['event_name_wrapper', 'event_id']);
    
    // Fetch full event details for storage and emails.
    $event_info = $this->database->select('event_configuration', 'e')
      ->fields('e')
      ->condition('id', $event_id)
      ->execute()
      ->fetchObject();

    if (!$event_info) {
      $this->messenger()->addError($this->t('Invalid event selected.'));
      return;
    }

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

    // Prepare mail params.
    $params = [
      'name' => $values['name'],
      'email' => $values['email'],
      'event_name' => $values['event_name'],
      'event_date' => $values['event_date'],
      'category' => $values['category'],
      'college' => $values['college'],
      'department' => $values['department'],
    ];

    // Send to User.
    $this->mailManager->mail('event_registration', 'registration_confirmation', $values['email'], 'en', $params, NULL, TRUE);

    // Send to Admin if enabled.
    $config = $this->configFactory->get('event_registration.settings');
    if ($config->get('enable_admin_notifications')) {
      $admin_email = $config->get('admin_email') ?? \Drupal::config('system.site')->get('mail');
      $this->mailManager->mail('event_registration', 'admin_notification', $admin_email, 'en', $params, NULL, TRUE);
      $this->mailManager->mail('event_registration', 'admin_notification', $admin_email, 'en', $params, NULL, TRUE);
    }
    
    $this->messenger()->addStatus($this->t('Registration successful! Check your email for confirmation.'));
  
  }
}
