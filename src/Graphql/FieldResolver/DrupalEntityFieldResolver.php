<?php

namespace Drupal\simple_graphql\Graphql\FieldResolver;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use GraphQL\Type\Definition\ResolveInfo;

class DrupalEntityFieldResolver extends FieldResolverBase {
  public function applies(ContentEntityInterface $entity, $args, $context, ResolveInfo $info) {
    if (in_array($info->fieldName, ["id", "path"])) {
      return true;
    }
    return $entity->hasField($info->fieldName) &&
      $entity
        ->getFieldDefinition($info->fieldName)
        ->getFieldStorageDefinition()
        ->getPropertyDefinition("value");
  }

  public function resolve(ContentEntityInterface $entity, $args, $context, ResolveInfo $info) {
    if ($info->fieldName === "id") {
      return $entity->uuid();
    }

    if ($info->fieldName === "path") {
      return $entity->toUrl()->toString();
    }

    return parent::resolve($entity, $args, $context, $info);
  }

  public function resolveRow(TypedDataInterface $item, $definition, $args, $context, ResolveInfo $info) {
    return $item->value;
  }
}
