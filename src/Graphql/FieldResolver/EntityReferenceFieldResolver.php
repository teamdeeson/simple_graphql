<?php

namespace Drupal\simple_graphql\Graphql\FieldResolver;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use GraphQL\Type\Definition\ResolveInfo;

class EntityReferenceFieldResolver extends FieldResolverBase {
  public function applies(ContentEntityInterface $entity, $args, $context, ResolveInfo $info) {
    return $entity->hasField($info->fieldName) &&
      $entity->getFieldDefinition($info->fieldName)->getType() === "entity_reference";
  }

  public function resolveRow(TypedDataInterface $item, $definition, $args, $context, ResolveInfo $info) {
    return $item->entity;
  }
}
