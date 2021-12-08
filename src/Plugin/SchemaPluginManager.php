<?php

namespace Drupal\simple_graphql\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

class SchemaPluginManager extends DefaultPluginManager {
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler
  ) {
    parent::__construct(
      "Plugin/SimpleGraphql",
      $namespaces,
      $module_handler,
      "Drupal\simple_graphql\Plugin\SchemaInterface",
      "Drupal\simple_graphql\Annotation\Schema",
    );
    $this->alterInfo("simple_graphql_schema");
    $this->setCacheBackend($cache_backend, "simple_graphql_schema");
  }
}
