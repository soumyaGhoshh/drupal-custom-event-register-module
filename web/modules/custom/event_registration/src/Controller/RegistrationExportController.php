<?php

namespace Drupal\event_registration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for exporting registrations to CSV.
 */
class RegistrationExportController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new RegistrationExportController.
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
   * Exports filtered registrations as CSV.
   */
  public function export(Request $request) {
    $date = $request->query->get('date');
    $name = $request->query->get('name');

    $query = $this->database->select('event_registration', 'r')
      ->fields('r');

    if ($date) {
      $query->condition('event_date', $date);
    }
    if ($name) {
      $query->condition('event_name', $name);
    }

    $results = $query->execute()->fetchAll();

    $handle = fopen('php://temp', 'r+');
    
    // Header row.
    fputcsv($handle, ['ID', 'Name', 'Email', 'College', 'Department', 'Category', 'Event Name', 'Event Date', 'Submission Date']);

    foreach ($results as $registration) {
      fputcsv($handle, [
        $registration->id,
        $registration->name,
        $registration->email,
        $registration->college,
        $registration->department,
        $registration->category,
        $registration->event_name,
        $registration->event_date,
        date('Y-m-d H:i:s', $registration->created),
      ]);
    }

    rewind($handle);
    $csv_content = stream_get_contents($handle);
    fclose($handle);

    $response = new Response($csv_content);
    $response->headers->set('Content-Type', 'text/csv');
    $response->headers->set('Content-Disposition', 'attachment; filename="registrations_export.csv"');

    return $response;
  }

}
