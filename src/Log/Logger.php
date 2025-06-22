<?php

declare(strict_types=1);

namespace Drupal\drush_batch_bar\Log;

use Drush\Log\Logger as DrushLogger;

/**
 * Custom logger that provides simplified message output for Drush.
 */
class Logger extends DrushLogger {

  /**
   * Displays a title message without additional spacing.
   *
   * @param string $message
   *   The title message to display.
   */
  public function simpleTitle(string $message): void {
    $this->output('title', $message);
  }

  /**
   * Displays a success message with one line before.
   *
   * @param string $message
   *   The success message to display.
   */
  public function simpleSuccess(string $message): void {
    $this->output('success', $message, 1);
  }

  /**
   * Displays an error message with one line before.
   *
   * @param string $message
   *   The error message to display.
   */
  public function simpleError(string $message): void {
    $this->output('error', $message, 1);
  }

  /**
   * Displays a warning message with one line before.
   *
   * @param string $message
   *   The warning message to display.
   */
  public function simpleWarning(string $message): void {
    $this->output('warning', $message, 1);
  }

  /**
   * Outputs a message to the Drush stream with optional spacing.
   *
   * @param string $method
   *   The method name to call on the stream (e.g., 'title', 'success', 'warning', 'error').
   * @param string $message
   *   The message to output.
   * @param int $newline_before
   *   (optional) The number of new lines to insert before the message. Default to 0.
   */
  private function output(string $method, string $message, int $newline_before = 0): void {
    $stream = $this->getErrorStreamWrapper();

    for ($i = 0; $i < $newline_before; $i++) {
      $stream->newLine();
    }

    if (method_exists($stream, $method)) {
      $stream->{$method}($message);
    }
    else {
      $stream->writeln($message);
    }
  }

}
