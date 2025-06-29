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
   * @param string $batch_name
   *   The batch name if existing.
   *
   * @return array
   *   The batch operations.
   */
  public static function operations(string $batch_name = ''): array {
    $operations = [];

    for ($example = 1; $example <= 10; $example++) {
      $operations[] = [
        [self::class, 'process'],
        [
          $example,
          $batch_name
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
   * @param string $batch_name
   *   The batch name if existing.
   * @param array<mixed> $context
   *   The batch context.
   */
  public static function process(int $example, string $batch_name, array &$context): void {
    parent::initProcess($context);

    try {
      // Implements your logic here.
      sleep($example);

      if ($batch_name === 'Second batch' && $example === 2) {
        // Force an error.
        throw new \UnexpectedValueException("Bad value for example n°$example.");
      }

      $context['results']['success']++;
    }
    catch (\Throwable $exception) {
      \Drupal::logger('drush_batch_bar_example')->error($exception->getMessage());
      $context['results']['error']++;
    }
  }

  /**
   * Simulate a batch error.
   *
   * @param bool $success
   *   Success.
   * @param array<string, int> $results
   *   Results.
   * @param array<int, array{0: callable, 1: array<int, mixed>}> $operations
   *   Operations launched.
   */
  public static function finished(bool $success, array $results, array $operations): void {
    parent::finished(FALSE, $results, $operations);
  }

}
