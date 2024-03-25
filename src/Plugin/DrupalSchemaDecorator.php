<?php
namespace Drupal\simple_graphql\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\simple_graphql\Graphql\FieldResolver\FieldResolverInterface;
use Drupal\simple_graphql\Graphql\FieldResolver\MissingFieldResolverException;
use GraphQL\Language\AST\InterfaceTypeDefinitionNode;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Language\AST\TypeDefinitionNode;
use GraphQL\Language\AST\UnionTypeDefinitionNode;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Schema;
use GraphQL\Server\ServerConfig;
use GraphQL\Type\Definition\ResolveInfo;

class DrupalSchemaDecorator {
  protected CacheBackendInterface $cache;

  public function __construct(CacheBackendInterface $cache) {
    $this->cache = $cache;
  }

  public function getTypeConfigDecorator(array $fieldResolvers) {
    $resolveEntityField = $this->getResolveEntityField($fieldResolvers);

    return function ($typeConfig, TypeDefinitionNode $typeDefinitionNode) use ($resolveEntityField) {
      if (
        $typeDefinitionNode instanceof InterfaceTypeDefinitionNode ||
        $typeDefinitionNode instanceof UnionTypeDefinitionNode
      ) {
        foreach ($typeDefinitionNode->directives as $d) {
          if ($d->name->value === "entityType") {
            $typeConfig["resolveType"] = [$this, "resolveEntityType"];
            break;
          }
        }
      }

      if ($typeDefinitionNode instanceof ObjectTypeDefinitionNode) {
        foreach ($typeDefinitionNode->directives as $d) {
          if ($d->name->value === "entity") {
            // todo we can also have a "isTypeOf" function which might enable us to remove the resolveEntityType, and the map.
            $typeConfig["resolveField"] = $resolveEntityField;
            break;
          }
        }
      }

      return $typeConfig;
    };
  }

  public function getResolveEntityField(array $fieldResolvers) {
    return function (ContentEntityInterface $entity, $args, $context, ResolveInfo $info) use ($fieldResolvers) {
      /** @var FieldResolverInterface */
      foreach ($fieldResolvers as $resolver) {
        if ($resolver->applies($entity, $args, $context, $info)) {
          return $resolver->resolve($entity, $args, $context, $info);
        }
      }
      throw new MissingFieldResolverException($entity, $info);
    };
  }

  public function resolveEntityType(EntityInterface $entity, $context, ResolveInfo $info) {
    foreach ($context["entityTypeMap"] as $mapping) {
      if ($mapping["entityType"] === $entity->getEntityTypeId() && $mapping["bundle"] === $entity->bundle()) {
        return $mapping["type"];
      }
    }

    throw new \Exception("Cannot identify type for {$entity->getEntityTypeId()} {$entity->bundle()}");
  }

  public function withDrupal(ServerConfig $config): ServerConfig {
    $context = $config->getContext();
    $context["entityTypeMap"] = $this->entityTypeMap($config->getSchema(), $context["pluginId"]);
    $config->setContext($context);

    return $config;
  }

  public function entityTypeMap(Schema $schema, string $pluginId) {
    // TODO is it worth caching? If so, then we should add the missing cache->set
    $entityTypeMapCache = $this->cache->get("simple_graphql.entityTypeMap." . $pluginId);

    $entityTypeMap = [];

    if ($entityTypeMapCache) {
      $entityTypeMap = $entityTypeMapCache->data;
    } else {
      $typeMap = $schema->getTypeMap();

      foreach ($typeMap as $type) {
        if ($type instanceof ObjectType && $type->astNode) {
          foreach ($type->astNode->directives as $d) {
            if ($d->name->value === "entity") {
              $info = [
                "type" => $type->name,
                "entityType" => "",
                "bundle" => "",
              ];
              foreach ($d->arguments as $arg) {
                if ($arg->name->value === "type") {
                  $info["entityType"] = $arg->value->value;
                } elseif ($arg->name->value === "bundle") {
                  $info["bundle"] = $arg->value->value;
                }
              }
              $entityTypeMap[$type->name] = $info;
              break;
            }
          }
        }
      }
    }
    return $entityTypeMap;
  }
}
