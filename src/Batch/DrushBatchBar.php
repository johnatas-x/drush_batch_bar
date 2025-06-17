<?php

declare(strict_types=1);

namespace Drupal\drush_batch_bar\Batch;

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
   * Init batch processes.
   *
   * @param array<mixed> $context
   *   The batch context.
   */
  protected static function initProcess(array &$context): void {
    $context['results']['success'] ??= 0;
    $context['results']['error'] ??= 0;
  }

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
  protected static function finished(bool $success, array $results, array $operations): void {
    if ($success === TRUE) {
      \Drupal::messenger()->addStatus(
        \Drupal::translation()->translate(
          '@success @success_message, @error @error_message.',
          [
            '@success' => $results['success'] ?? 0,
            '@success_message' => static::SUCCESS_MESSAGE,
            '@error' => $results['error'] ?? 0,
            '@error_message' => static::ERROR_MESSAGE,
          ]
        )
      );

      return;
    }

    $error_operation = reset($operations);

    if (!is_array($error_operation)) {
      \Drupal::messenger()->addError(\Drupal::translation()->translate('An unknown error occurred.'));

      return;
    }

    \Drupal::messenger()->addError(
      \Drupal::translation()->translate(
        'An error occurred during process of @operation with args : @args',
        [
          '@operation' => $error_operation[0],
          '@args' => print_r($error_operation[1], TRUE),
        ]
      )
    );
  }

}
