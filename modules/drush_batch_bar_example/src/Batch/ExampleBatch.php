<?php

declare(strict_types=1);

namespace Drupal\drush_batch_bar_example\Batch;

use Drupal\drush_batch_bar\Batch\DrushBatchBar;

/**
 * Batch methods example.
 */
class ExampleBatch extends DrushBatchBar {

  /**
   * The finished success message.
   */
  protected const string SUCCESS_MESSAGE = 'examples success';

  /**
   * Operation maker.
   *
   * @return array
   *   The batch operations.
   */
  public static function operations(): array {
    $operations = [];

    for ($example = 1; $example <= 50; $example++) {
      $operations[] = [
        [self::class, 'process'],
        [
          $example,
        ],
      ];
    }

    return $operations;
  }

  /**
   * Process example.
   *
   * @param int $example
   *   The ball number.
   * @param array<mixed> $context
   *   The batch context.
   */
  public static function process(int $example, array &$context): void {
    parent::initProcess($context);

    try {
      // Implements your logic here.
      echo "Example n°$example";
      $context['results']['success']++;
    }
    catch (\Throwable $exception) {
      $context['results']['error']++;
      $context['message'] = '[KO] ' . $exception->getMessage();
    }
  }

}
