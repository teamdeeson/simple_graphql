<?php

namespace Drupal\simple_graphql\Plugin;

use GraphQL\Language\AST\TypeDefinitionNode;
use GraphQL\Server\ServerConfig;

interface SchemaInterface {
  public function schemaTypeConfigDecorator($typeConfig, TypeDefinitionNode $typeDefinitionNode);
  public function configureServer(ServerConfig $serverConfig): ServerConfig;
}
