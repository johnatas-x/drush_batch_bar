<?php

declare(strict_types=1);

namespace Drupal\drush_batch_bar\Commands;

use Drupal\Core\Database\Connection;
use Drupal\Core\Queue\Batch;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\drush_batch_bar\Log\Logger;
use Drush\Commands\DrushCommands;
use Drush\Drush;
use Drush\Style\DrushStyle;

/**
 * Base class for batched Drush commands.
 */
class ProcessCommands extends DrushCommands {

  use StringTranslationTrait;

  /**
   * Maximum percentage of a batch.
   */
  private const int MAX_PERCENTAGE = 100;

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * ProcessCommands constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database service.
   */
  public function __construct(Connection $database) {
    parent::__construct();

    $this->database = $database;
  }

  /**
   * Progress bar management.
   *
   * @param string $id
   *   The batch ID.
   * @param array<mixed> $options
   *   The batch options.
   *
   * @option format
   *   Drush output format.
   *
   * @command drush_batch_bar:process
   *
   * @aliases drush-batch-bar-process
   *
   * @usage drush_batch_bar:process 123
   *   Process batch 123 with a progress bar.
   *
   * @return array<mixed>|false
   *   Batch ended or not.
   *
   * @SuppressWarnings("PHPMD.UnusedFormalParameter")
   */
  public function process(string $id, array $options = ['format' => 'json']): array|false {
    include_once DRUSH_DRUPAL_CORE . '/includes/batch.inc';
    $batch =& batch_get();

    $data = $this->database->select('batch', 'b')
      ->fields('b', ['batch'])
      ->condition('bid', (int) $id)
      ->execute()
      ?->fetchField();

    if (!is_string($data) || empty($data)) {
      return FALSE;
    }

    $batch = unserialize($data, ['allowed_classes' => FALSE]);

    if (!isset($batch['running'])) {
      $batch['running'] = TRUE;
    }

    register_shutdown_function('_drush_batch_shutdown');

    $logger = new Logger($this->output());

    if (!empty($batch['sets'][0]['title']) && is_string($batch['sets'][0]['title'])) {
      $logger->simpleTitle($batch['sets'][0]['title']);
    }

    if (static::drushProgressBatchWorker($this->io(), $logger)) {
      return _drush_batch_finished();
    }

    return ['drush_batch_process_finished' => FALSE];
  }

  /**
   * Progress worker.
   *
   * @param \Drush\Style\DrushStyle $io
   *   The current Drush style.
   * @param \Drupal\drush_batch_bar\Log\Logger $logger
   *   The current logger.
   *
   * @return bool
   *   TRUE if the batch is finished.
   */
  public static function drushProgressBatchWorker(DrushStyle $io, Logger $logger): bool {
    $current_set = &_batch_current_set();
    $set_changed = TRUE;
    $finished = 0;
    $drush_config = Drush::config();

    if (empty($current_set['start'])) {
      $current_set['start'] = microtime(TRUE);
    }

    $queue = _batch_queue($current_set);
    $io->setDecorated(TRUE);
    $io->progressStart($current_set['count']);

    while (!$current_set['success']) {
      if ($set_changed &&
        isset($current_set['file']) &&
        is_string($current_set['file']) &&
        is_file($current_set['file'])) {
        include_once DRUPAL_ROOT . '/' . $current_set['file'];
      }

      $task_message = '';
      $finished = 1;

      if ($queue instanceof Batch) {
        $item = $queue->claimItem();

        if (is_object($item)) {
          [$callback, $args] = $item->data;

          $batch_context = [
            'sandbox' => &$current_set['sandbox'],
            'results' => &$current_set['results'],
            'finished' => &$finished,
            'message' => &$task_message,
          ];

          $halt_on_error = $drush_config->get('runtime.php.halt-on-error', 'TRUE');
          $drush_config->set('runtime.php.halt-on-error', 'FALSE');
          call_user_func_array($callback, array_merge($args, [&$batch_context]));
          $io->progressAdvance();
          $drush_config->set('runtime.php.halt-on-error', $halt_on_error);

          if ($finished >= 1) {
            $finished = 0;
            $queue->deleteItem($item);
            $current_set['count']--;
            $current_set['sandbox'] = [];
          }
        }
      }

      $set_changed = FALSE;
      $old_set = $current_set;

      while (empty($current_set['count']) && ($current_set['success'] = TRUE) && _batch_next_set()) {
        $current_set = &_batch_current_set();
        $current_set['start'] = microtime(TRUE);
        $set_changed = TRUE;
      }

      $queue = _batch_queue($current_set);

      if (drush_memory_limit() > 0 && (memory_get_usage() * 1.6) >= drush_memory_limit()) {
        $logger->simpleWarning(
          dt('Batch process has consumed in excess of 60% of available memory. Starting new thread')
        );
        $current_set['elapsed'] = round((microtime(TRUE) - $current_set['start']) * 1_000, 2);

        break;
      }
    }

    $io->progressFinish();

    if ($set_changed && isset($current_set['queue'])) {
      $remaining = $current_set['count'];
      $total = $current_set['total'];
    }
    else {
      $remaining = $old_set['count'] ?? $current_set['count'];
      $total = $old_set['total'] ?? $current_set['total'];
    }

    $current = $total - $remaining + $finished;
    $percentage = _batch_api_percentage((int) $total, $current);

    return ((int) $percentage === self::MAX_PERCENTAGE);
  }

}
