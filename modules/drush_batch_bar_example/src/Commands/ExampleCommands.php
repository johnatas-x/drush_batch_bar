<?php

declare(strict_types=1);

namespace Drupal\drush_batch_bar_example\Commands;

use Drupal\drush_batch_bar\Commands\DrushBatchCommands;
use Drupal\drush_batch_bar_example\Batch\ExampleBatch;
use Drush\Commands\DrushCommands;

/**
 * Drush Batch Bar commands example.
 */
class ExampleCommands extends DrushCommands {

  /**
   * Simple example.
   *
   * @command drush-batch-bar
   *
   * @aliases dbb
   *
   * @usage drush drush-batch-bar
   *   Make an example.
   */
  public function example(): void {
    $batch = new DrushBatchCommands(
      operations: ExampleBatch::operations(),
      title: "DBB example",
      finished: [
        ExampleBatch::class,
        'finished',
      ]
    );

    $batch->execute();
  }

  /**
   * Multiple batch example.
   *
   * @command drush-batch-bar-multiple
   *
   * @aliases dbbm
   *
   * @usage drush drush-batch-bar-multiple
   *   Make an example.
   */
  public function multipleExample(): void {
    $batches = [
      'first batch',
      'second batch',
      'third batch',
    ];

    foreach ($batches as $batch_name) {
      $batch = new DrushBatchCommands(
        operations: ExampleBatch::operations(),
        title: "$batch_name example",
        finished: [
          ExampleBatch::class,
          'finished',
        ]
      );

      $batch->execute();
    }
  }

}
