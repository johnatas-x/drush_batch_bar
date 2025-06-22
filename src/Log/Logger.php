<?php

declare(strict_types=1);

namespace Drupal\drush_batch_bar\Log;

use Drush\Log\Logger as DrushLogger;

class Logger extends DrushLogger {

  public function logByMethod(string|\Stringable $message, string $method): void
  {
    $stream = $this->getErrorStreamWrapper();
    $stream->$method($message);
  }

}
