<?php

declare(strict_types=1);

namespace Drupal\drush_batch_bar\Commands;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drush\Commands\DrushCommands;

/**
 * Base class for batched Drush commands.
 */
class ProcessCommands extends DrushCommands {

  use StringTranslationTrait;

  /**
   * Progress bar management.
   *
   * @param $id
   *   The batch ID.
   *
   * @option format
   *   Drush output format.
   *
   * @command drush_batch_bar:process
   *
   * @aliases drush-batch-bar-process
   *
   * @usage drush fill-lotto-stats
   *   Fill stats to DB.
   *
   * @return array|false
   *   Batch ended or not.
   */
  public function process($id, $options = ['format' => 'json']): array|false {
    include_once(DRUSH_DRUPAL_CORE . '/includes/batch.inc');
    $batch =& batch_get();

    $data = \Drupal\Core\Database\Database::getConnection()->select('batch', 'b')
      ->fields('b', ['batch'])
      ->condition('bid', (int) $id)
      ->execute()
      ->fetchField();

    if ($data) {
      $batch = unserialize($data, ['allowed_classes' => FALSE]);
    }
    else {
      return FALSE;
    }

    if (!isset($batch['running'])) {
      $batch['running'] = TRUE;
    }

    // Register database update for end of processing.
    register_shutdown_function('_drush_batch_shutdown');

    if (static::drush_progress_batch_worker($this->io())) {
      return _drush_batch_finished();
    }

    return ['drush_batch_process_finished' => FALSE];
  }

  public static function drush_progress_batch_worker(\Drush\Style\DrushStyle $io): bool {
    $batch = &batch_get();
    $current_set = &_batch_current_set();
    $set_changed = TRUE;

    if (empty($current_set['start'])) {
      $current_set['start'] = microtime(TRUE);
    }
    $queue = _batch_queue($current_set);
    $io->setDecorated(TRUE);
    $io->progressStart($current_set['count']);
    while (!$current_set['success']) {
      if ($set_changed && isset($current_set['file']) && is_file($current_set['file'])) {
        include_once DRUPAL_ROOT . '/' . $current_set['file'];
      }

      $task_message = '';
      $finished = 1;

      if ($item = $queue->claimItem()) {
        [$callback, $args] = $item->data;

        $batch_context = [
          'sandbox' => &$current_set['sandbox'],
          'results' => &$current_set['results'],
          'finished' => &$finished,
          'message' => &$task_message,
        ];

        $halt_on_error = \Drush\Drush::config()->get('runtime.php.halt-on-error', TRUE);
        \Drush\Drush::config()->set('runtime.php.halt-on-error', FALSE);
        call_user_func_array($callback, array_merge($args, [&$batch_context]));
        $io->progressAdvance();
        \Drush\Drush::config()->set('runtime.php.halt-on-error', $halt_on_error);

        if ($finished >= 1) {
          $finished = 0;
          $queue->deleteItem($item);
          $current_set['count']--;
          $current_set['sandbox'] = [];
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
        \Drush\Drush::logger()->notice(dt('Batch process has consumed in excess of 60% of available memory. Starting new thread'));
        $current_set['elapsed'] = round((microtime(TRUE) - $current_set['start']) * 1000, 2);
        break;
      }
    }
    $io->progressFinish();

    if ($set_changed && isset($current_set['queue'])) {
      $remaining = $current_set['count'];
      $total = $current_set['total'];
      $task_message = '';
    }
    else {
      $remaining = $old_set['count'];
      $total = $old_set['total'];
    }

    $current = $total - $remaining + $finished;
    $percentage = _batch_api_percentage($total, $current);
    return ($percentage == 100);
  }

}
