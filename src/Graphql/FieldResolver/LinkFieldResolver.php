<?php

namespace Drupal\simple_graphql\Graphql\FieldResolver;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\link\Plugin\Field\FieldType\LinkItem;
use Drupal\simple_graphql\Graphql\FieldResolver\FieldResolverInterface;
use GraphQL\Type\Definition\ResolveInfo;

abstract class FieldResolverBase implements FieldResolverInterface {
  public function applies(ContentEntityInterface $entity, $args, $context, ResolveInfo $info) {
    return $entity->hasField($info->fieldName) && $entity->getFieldDefinition($info->fieldName)->getType() === "link";
  }

  public function resolveRow(TypedDataInterface $item, $definition, $args, $context, ResolveInfo $info) {
    if ($item instanceof LinkItem) {
      return [
        "url" => $item->getUrl()->toString(),
        "title" => $item->title,
      ];
    }
  }
}
