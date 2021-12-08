<?php

namespace Drupal\simple_graphql\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Annotation for Simple GraphQL schema.
 *
 * @Annotation
 */
class Schema extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * Drupal endpoint for accessing the API.
   */
  public $path;

  /**
   * File system path for a GraphQL schema file.
   */
  public $schemaFile;

}
