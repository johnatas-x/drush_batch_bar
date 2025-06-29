## CONTENTS OF THIS FILE

- Introduction
- Requirements
- Installation
- Recommended modules
- Configuration
- Usage
- Maintainers

## INTRODUCTION

The Drush Batch Bar module is for easily build Drush commands with batch operations and a clean Symfony progress bar output for the CLI.

The goal is to easily monitor the progress of batches triggered via Drush commands, just like you would in the Back Office, without cluttering the output by logging each operation.

This module also makes it easy to run batches through Drush commands.

## REQUIREMENTS

- PHP >= 8.4
- Drupal core >= 11.0
- Drush >= 13

## INSTALLATION

The installation of this module is like other Drupal modules.

1. If your site is [managed via Composer](https://www.drupal.org/node/2718229),
   use Composer to download the webform module running
   ```composer require "drupal/drush_batch_bar"```. Otherwise copy/upload the module to the modules directory of your Drupal installation.

2. Enable the 'Drush Batch Bar' module in 'Extend' (`/admin/modules`) or via Drush (`drush en drush_batch_bar`).

## RECOMMENDED MODULES

- No extra module is required.

## CONFIGURATION

- No configuration is needed.

## USAGE

Create a Drush command as you normally would, and in the command method, instantiate a `new DrushBatchCommands()` and execute it.

```php
$batch = new DrushBatchCommands(
  operations: $batch_operations,
  title: 'Title of your batch',
  finished: [
    DrushBatchBar::class,
    'finished',
  ]
);

$batch->execute();
```

The `$batch_operations` variable should be an array containing your batch operations.  
You can override the `finished` parameter with your own custom method if needed.

If necessary, you can also create your own Batch class by extending `DrushBatchBar`.  
In it, you can define your custom operations, the process method (where you can call `parent::initProcess($context);`), and the finish method.

> [!TIP]
> You can see an example implementation in the `drush_batch_bar_example` module.
> You can also install this module and run the `drush dbb` command to see a sample output.
>
> Multiple batches can be triggered within the same Drush command.
> Similarly, an example implementation is available in the `drush_batch_bar_example` module, and the corresponding command is drush dbbm.

## MAINTAINERS

Current maintainers:

- Sylvain Vanel (johnatas) - https://www.drupal.org/u/johnatas
