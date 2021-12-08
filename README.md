This Drpual module is an alternative to https://www.drupal.org/project/graphql.

It's designed for use by developers to create a custom Graphql schema. At it's most simple, this module is just a wrapper around https://webonyx.github.io/graphql-php/ but it also
allows you to compose the schema with a bunch of Drupal-specific functionality for handling entities, bundles, fields, entity referneces and more.

## Getting started

1. Download, install the module. There are no administration screens so nothing's going to look different at this point.
2. Set yourself up with a custom module.
3. Create a file in my_module/src/Pluing/SimpleGraphql. e.g. Schema.php.

```php
<?php
namespace Drupal\sn_graphql\Plugin\SimpleGraphql;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\node\Entity\Node;
use Drupal\simple_graphql\Plugin\DrupalSchemaDecorator;
use Drupal\simple_graphql\Plugin\SchemaInterface as PluginSchemaInterface;
use GraphQL\Language\AST\TypeDefinitionNode;
use GraphQL\Server\ServerConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @Schema(
 *  id = "my_module_graphql_schema",
 *  path = "/graphql",
 *  schemaFile = "src/schema.graphql",
 * )
 */
class Schema implements PluginSchemaInterface, ContainerFactoryPluginInterface {
  protected DrupalSchemaDecorator $drupalSchemaDecorator;

  protected $typeConfigDecorator;

  public function __construct(DrupalSchemaDecorator $drupalSchemaDecorator, array $fieldResolvers) {
    $this->drupalSchemaDecorator = $drupalSchemaDecorator;
    $this->typeConfigDecorator = $this->drupalSchemaDecorator->getTypeConfigDecorator($fieldResolvers);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $fieldResolvers = [
      $container->get("simple_graphql.graphql.fieldResolver.entityReferenceFieldResolver"),
      $container->get("simple_graphql.graphql.fieldResolver.nodeFieldResolver"),
      $container->get("simple_graphql.graphql.fieldResolver.textFieldResolver"),
      $container->get("simple_graphql.graphql.fieldResolver.drupalEntityFieldResolver"),
    ];
    return new self($container->get("simple_graphql.plugin.drupalSchemaDecorator"), $fieldResolvers);
  }

  public function schemaTypeConfigDecorator($typeConfig, TypeDefinitionNode $typeDefinitionNode) {
    return ($this->typeConfigDecorator)($typeConfig, $typeDefinitionNode);
  }

  public function configureServer(ServerConfig $serverConfig): ServerConfig {
    $serverConfig->setRootValue(["node" => Node::load(1)]);

    return $this->drupalSchemaDecorator->withDrupal($serverConfig);
  }
}
```

4. Create a schema file, e.g schema.graphql

```graphql
type Query {
  node: Node
}

interface FormattedFieldBase {
  value: String!
  format: String!
  processed: String!
}

type FormattedField implements FormattedFieldBase {
  value: String!
  format: String!
  processed: String!
}

type FormattedFieldWithSummary implements FormattedFieldBase {
  value: String!
  format: String!
  processed: String!
  summary_computed: String!
}

type LinkField {
  url: String!
}

type LinkFieldWithTitle {
  url: String!
  title: String!
}

type LinkFieldWithTitleAndChildren {
  url: String!
  title: String!
  children: [LinkFieldWithTitle!]
}

#
# Content types
#

enum VersionStatus {
  PUBLISHED
  DRAFT
  PREVIOUS_REVISION
  PREVIEW
}

interface Node @entityType {
  id: String!
  title: String!
  status: Boolean!
  path: String!
  editUrl: String
  defaultVersionUrl: String
  latestVersionUrl: String
  versionStatus: VersionStatus
}

directive @entityType on INTERFACE | UNION
directive @entity(type: String!, bundle: String!) on OBJECT

type BasicPageNode implements Node @entity(type: "node", bundle: "page") {
  id: String!
  title: String!
  status: Boolean!
  path: String!
  editUrl: String
  defaultVersionUrl: String
  latestVersionUrl: String
  versionStatus: VersionStatus
  body(trimLength: Int = 120): FormattedFieldWithSummary
}
```

5. Clear cache. You'll also need to do this any time you make a change to the schema file. You should now have an API up and running at /graphql.

### Notes

There's no routing at the moment. The example above just loads and returns the node with id 1. I've got some code ready for routing, just haven't had time to put it in yet.
