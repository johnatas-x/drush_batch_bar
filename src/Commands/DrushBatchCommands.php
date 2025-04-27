<?php

declare(strict_types=1);

namespace Drupal\drush_batch_bar\Commands;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\drush_batch_bar\Batch\DrushBatchBar;
use Drush\Commands\DrushCommands;
use Drush\Style\DrushStyle;

/**
 * Base class for batched Drush commands.
 */
class DrushBatchCommands extends DrushCommands {

  use StringTranslationTrait;

  /**
   * Default init and error messages.
   */
  private const string DEFAULT_INIT_MESSAGE = 'Initialization...';
  private const string DEFAULT_ERROR_MESSAGE = 'An unexpected error occurred.';

  /**
   * Default finished method.
   *
   * @var array{0: object|string, 1: string}
   */
  private const array DEFAULT_FINISHED = [
    DrushBatchBar::class,
    'finished'
  ];

  /*
   * PHPCS is not yet 100% compatible with PHP 8.4,
   * so we are forced to ignore the "Property hooks" and the "Asymmetric Visibility" as long as they are not supported.
   *
   * phpcs:disable
   */

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
   * Number of batch operations.
   *
   * @var ?int
   */
  protected private(set) ?int $batchOperations {
    set (?int $batchOperations) => $this->batchOperations = $batchOperations ?? count($this->operations);
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

  /*
   * Re-enable PHPCS for the rest of the file.
   *
   * phpcs:enable
   */

  /**
   * DrushBatchCommands constructor.
   *
   * @param array<mixed> $operations
   *   Batch operations.
   * @param \Drush\Style\DrushStyle $drush_io
   *   The drush IO.
   * @param string $title
   *   Batch title.
   * @param string $initMessage
   *   Batch init message.
   * @param string $errorMessage
   *   Batch error message.
   * @param array{0: object|string, 1: string} $finished
   *   Batch finished method.
   * @param int|null $batchOperations
   *   Number of batch operations.
   */
  public function __construct(
    public readonly array $operations,
    protected DrushStyle $drush_io,
    string $title,
    string $initMessage = self::DEFAULT_INIT_MESSAGE,
    string $errorMessage = self::DEFAULT_ERROR_MESSAGE,
    array $finished = self::DEFAULT_FINISHED,
    ?int $batchOperations = NULL,
  ) {
    parent::__construct();

    $this->io = $drush_io;
    $this->title = $title;
    $this->initMessage = $initMessage;
    $this->errorMessage = $errorMessage;
    $this->finished = $finished;
    $this->batchOperations = $batchOperations;
  }

  /**
   * Execute the drush command.
   */
  public function execute(): void {
    // Start the progress bar.
    $this->io->progressStart($this->batchOperations ??= 1);

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

    // End the progress bar.
    $this->io->progressFinish();
  }

}
