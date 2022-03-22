<?php

namespace Drupal\simple_graphql\Graphql\FieldResolver;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use GraphQL\Type\Definition\ResolveInfo;

class EntityReferenceFieldResolver extends DrupalEntityFieldResolver {
  public function applies(ContentEntityInterface $entity, $args, $context, ResolveInfo $info) {
    return $entity->hasField($info->fieldName) &&
      $entity->getFieldDefinition($info->fieldName)->getType() === "entity_reference";
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
    $myFields = [];
    foreach ($item->entity->getFields() as $name => $field) {
      $myFields[$name] = $field->getValue();
    }
    $myFields['id'] = $item->entity->id();
    $myFields['title'] = $item->entity->getTitle();
    return $myFields;
  }
}
