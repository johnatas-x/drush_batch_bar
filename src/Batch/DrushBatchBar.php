<?php

declare(strict_types=1);

namespace Drupal\drush_batch_bar\Batch;

/**
 * Base class for all batches with a progress bar.
 */
abstract class DrushBatchBar {

  /**
   * Init batch processes.
   *
   * @param string $details
   *   Details to follow command progress.
   * @param array<mixed> $context
   *   The batch context.
   */
  protected static function initProcess(string $details, array &$context): void {
    $context['message'] = "\n$details\n";
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
   * @param string $success_message
   *   The success message.
   */
  protected static function finished(bool $success, array $results, array $operations, string $success_message = 'success'): void {
    if ($success === TRUE) {
      \Drupal::messenger()->addStatus(
        \Drupal::translation()->translate(
          '@success @success_message, @error errors.',
          [
            '@success' => $results['success'] ?? 0,
            '@success_message' => $success_message,
            '@error' => $results['error'] ?? 0,
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
