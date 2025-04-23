<?php

declare(strict_types=1);

namespace Drupal\drush_batch_bar\Commands;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drush\Commands\DrushCommands;

/**
 * Base class for batched Drush commands.
 */
class DrushBatchCommands extends DrushCommands {

  use StringTranslationTrait;

  /**
   * The title of the batch process.
   *
   * @var string
   */
  protected private(set) string $title {
    set (string $title) => $this->title = $this->t($title)->render();
  }

  /**
   * The message to display when the batch process starts.
   *
   * @var string
   */
  protected private(set) string $initMessage {
    set (string $initMessage) => $this->initMessage = $this->t($initMessage)->render();
  }

  /**
   * The message to display when the batch process fails.
   *
   * @var string
   */
  protected private(set) string $errorMessage {
    set (string $errorMessage) => $this->errorMessage = $this->t($errorMessage)->render();
  }

  /**
   * The method to call when the batch process is finished.
   *
   * @var array{0: object|string, 1: string}
   */
  protected private(set) array $finished {
    set (array $finished) {
      if (empty($finished[0]) || empty($finished[1])) {
        throw new \InvalidArgumentException(
          'The first and second elements of the finished array must be set.'
        );
      }

      if (!is_object($finished[0]) && !is_string($finished[0]) && !is_string($finished[1])) {
        throw new \InvalidArgumentException(
          'The first element of the finished array must be an object or a string and the second element must be a string.'
        );
      }

      if (!method_exists($finished[0], $finished[1])) {
        throw new \InvalidArgumentException(
          'The second element of the finished array must be a method of the first element of the finished array.'
        );
      }

      $this->finished = array_slice($finished, 0, 2);
    }
  }

  /**
   * DrushBatchCommands constructor.
   *
   * @param array<mixed> $operations
   * @param string $title
   * @param string $initMessage
   * @param string $errorMessage
   * @param array{0: object|string, 1: string} $finished
   */
  public function __construct(public readonly array $operations, string $title, string $initMessage, string $errorMessage, array $finished)
  {
    parent::__construct();

    $this->title = $title;
    $this->initMessage = $initMessage;
    $this->errorMessage = $errorMessage;
    $this->finished = $finished;
  }

  /**
   * Execute the drush command.
   */
  public function execute(): void {
    // Put all necessary information into a batch array.
    $batch = [
      'operations' => $this->operations,
      'title' => $this->title,
      'init_message' => $this->initMessage,
      'error_message' => $this->errorMessage,
      'finished' => $this->finished,
    ];

    // Get the batch process all ready.
    batch_set($batch);
    $batch =& batch_get();

    // Because we are doing this on the back-end, we set progressive to false.
    $batch['progressive'] = FALSE;

    // Start processing the batch operations.
    drush_backend_batch_process();
  }

}
