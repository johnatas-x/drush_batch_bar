<?php

declare(strict_types=1);

namespace Drupal\drush_batch_bar\Batch;

use Drupal\drush_batch_bar\Log\Logger;
use Drush\Drush;

/**
 * Base class for all batches with a progress bar.
 */
abstract class DrushBatchBar {

  /**
   * Default finished messages.
   */
  protected const string SUCCESS_MESSAGE = 'success';
  protected const string ERROR_MESSAGE = 'errors';

  /**
   * Default method to run at the end of the batch treatment.
   *
   * @param bool $success
   *   Success.
   * @param array<string, int> $results
   *   Results.
   * @param array<int, array{0: callable, 1: array<int, mixed>}> $operations
   *   Operations launched.
   */
  public static function finished(bool $success, array $results, array $operations): void {
    $logger = new Logger(Drush::output());

    if ($success === TRUE) {
      $message = \Drupal::translation()->translate(
        '@success @success_message, @error @error_message.',
        [
          '@success' => $results['success'] ?? 0,
          '@success_message' => static::SUCCESS_MESSAGE,
          '@error' => $results['error'] ?? 0,
          '@error_message' => static::ERROR_MESSAGE,
        ]
      )->render();

      if ($results['error'] ?? 0) {
        $logger->simpleWarning($message);
      }
      else {
        $logger->simpleSuccess($message);
      }

      return;
    }

    $error_operation = reset($operations);

    if (!is_array($error_operation)) {
      $logger->simpleError(
        \Drupal::translation()->translate('An unknown error occurred.')->render());

      return;
    }

    $logger->simpleError(
      \Drupal::translation()->translate(
        'An error occurred during process of @operation with args : @args',
        [
          '@operation' => $error_operation[0],
          '@args' => print_r($error_operation[1], TRUE),
        ]
      )->render()
    );
  }

  /**
   * Init batch processes.
   *
   * @param array<mixed> $context
   *   The batch context.
   */
  protected static function initProcess(array &$context): void {
    $context['results']['success'] ??= 0;
    $context['results']['error'] ??= 0;
  }

}
