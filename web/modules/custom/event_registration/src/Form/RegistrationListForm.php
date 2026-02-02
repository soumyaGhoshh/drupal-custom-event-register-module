<?php

namespace Drupal\event_registration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an admin listing of registrations with advanced AJAX filters.
 */
class RegistrationListForm extends FormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new RegistrationListForm.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'event_registration_admin_list';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['filters'] = [
      '#type' => 'details',
      '#title' => $this->t('Filters'),
      '#open' => TRUE,
      '#attributes' => ['class' => ['container-inline']],
    ];

    // Get all unique dates from registrations.
    $event_dates = $this->database->select('event_registration', 'r')
      ->fields('r', ['event_date'])
      ->distinct()
      ->execute()
      ->fetchCol();

    $form['filters']['event_date'] = [
      '#type' => 'select',
      '#title' => $this->t('Event Date'),
      '#options' => ['' => $this->t('- Select Date -')] + array_combine($event_dates, $event_dates),
      '#ajax' => [
        'callback' => '::updateEventNamesAndTable',
        'wrapper' => 'admin-list-ajax-wrapper',
      ],
    ];

    $selected_date = $form_state->getValue('event_date');
    $name_options = ['' => $this->t('- Select Date First -')];

    if ($selected_date) {
      $names = $this->database->select('event_registration', 'r')
        ->fields('r', ['event_name'])
        ->condition('event_date', $selected_date)
        ->distinct()
        ->execute()
        ->fetchCol();
      $name_options = ['' => $this->t('- All Events -')] + array_combine($names, $names);
    }

    $form['filters']['event_name_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'event-name-filter-wrapper', 'style' => 'display:inline-block;'],
    ];

    $form['filters']['event_name_wrapper']['event_name'] = [
      '#type' => 'select',
      '#title' => $this->t('Event Name'),
      '#options' => $name_options,
      '#disabled' => empty($selected_date),
      '#ajax' => [
        'callback' => '::updateTable',
        'wrapper' => 'registration-table-wrapper',
      ],
    ];

    // Main wrapper for AJAX updates.
    $form['main_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'admin-list-ajax-wrapper'],
    ];

    $selected_name = $form_state->getValue(['event_name_wrapper', 'event_name']);

    // Build the query.
    $query = $this->database->select('event_registration', 'r')
      ->fields('r');

    if ($selected_date) {
      $query->condition('event_date', $selected_date);
    }
    if ($selected_name) {
      $query->condition('event_name', $selected_name);
    }

    $results = $query->execute()->fetchAll();
    $total_participants = count($results);

    // Total participants display.
    $form['main_wrapper']['total'] = [
      '#markup' => '<div class="messages messages--status">' . $this->t('Total Participants: @count', ['@count' => $total_participants]) . '</div>',
    ];

    // Table view.
    $header = [
      ['data' => $this->t('Name'), 'field' => 'name'],
      ['data' => $this->t('Email'), 'field' => 'email'],
      ['data' => $this->t('Event Date'), 'field' => 'event_date'],
      ['data' => $this->t('College Name'), 'field' => 'college'],
      ['data' => $this->t('Department'), 'field' => 'department'],
      ['data' => $this->t('Submission Date'), 'field' => 'submission_date'],
    ];

    $rows = [];
    foreach ($results as $reg) {
      $rows[] = [
        $reg->name,
        $reg->email,
        $reg->event_date,
        $reg->college,
        $reg->department,
        date('Y-m-d H:i:s', $reg->created),
      ];
    }

    $form['main_wrapper']['table_container'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'registration-table-wrapper'],
    ];

    $form['main_wrapper']['table_container']['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No registrations found for the selected filters.'),
    ];
        // Export Button.
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['export'] = [
      '#type' => 'link',
      '#title' => $this->t('Export as CSV'),
      '#url' => Url::fromRoute('event_registration.export_csv', [], [
        'query' => [
          'date' => $selected_date,
          'name' => $selected_name,
        ],
      ]),
      '#attributes' => ['class' => ['button', 'button--primary']],
    ];

    return $form;
  }

  /**
   * AJAX callback to update both the Event Name dropdown and the table.
   */
  public function updateEventNamesAndTable(array &$form, FormStateInterface $form_state) {
    $response = new \Drupal\Core\Ajax\AjaxResponse();
    $response->addCommand(new \Drupal\Core\Ajax\ReplaceCommand('#event-name-filter-wrapper', $form['filters']['event_name_wrapper']));
    $response->addCommand(new \Drupal\Core\Ajax\ReplaceCommand('#admin-list-ajax-wrapper', $form['main_wrapper']));
    return $response;
  }

  /**
   * AJAX callback to update just the table.
   */
  public function updateTable(array &$form, FormStateInterface $form_state) {
    return $form['main_wrapper'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Logic not required for listing form.
  }

}