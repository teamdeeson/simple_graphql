<?php

namespace Drupal\simple_graphql;

use Drupal\simple_graphql\Plugin\SchemaPluginManager;
use Symfony\Component\Routing\Route;

class Router {
  protected $pluginManager;

  public function __construct(SchemaPluginManager $pluginManager) {
    $this->pluginManager = $pluginManager;
  }

  public function getRoutes() {
    $routes = [];
    foreach ($this->pluginManager->getDefinitions() as $definition) {
      $routes["simple_graphql.api.{$definition["id"]}"] = new Route(
        $definition["path"],
        [
          "schema" => $definition["id"],
          "_controller" => "\Drupal\simple_graphql\Controller\GraphqlController::graphql",
        ],
        [
          "_access" => "TRUE",
        ],
      );
    }
    return $routes;
  }
}
