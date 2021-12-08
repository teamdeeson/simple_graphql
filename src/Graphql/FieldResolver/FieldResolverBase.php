<?php

namespace Drupal\simple_graphql\Graphql\FieldResolver;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\simple_graphql\Graphql\FieldResolver\FieldResolverInterface;
use GraphQL\Type\Definition\ResolveInfo;

abstract class FieldResolverBase implements FieldResolverInterface {
  public function resolve(ContentEntityInterface $entity, $args, $context, ResolveInfo $info) {
    $fieldItem = $entity->get($info->fieldName);
    $definition = $fieldItem->getFieldDefinition();
    $isArray = $definition->getFieldStorageDefinition()->isMultiple();

    if ($fieldItem->isEmpty()) {
      if ($isArray) {
        return [];
      }
      if ($definition->getType() === "boolean") {
        return false;
      }
      return null;
    }

    if ($isArray) {
      $map = [];
      foreach ($fieldItem as $i) {
        $map[] = $this->resolveRow($i, $definition, $args, $context, $info);
      }
      return $map;
    }
    return $this->resolveRow($fieldItem->first(), $definition, $args, $context, $info);
  }

  abstract public function resolveRow(TypedDataInterface $item, $definition, $args, $context, ResolveInfo $info);
}
