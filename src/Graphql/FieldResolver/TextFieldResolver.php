<?php

namespace Drupal\simple_graphql\Graphql\FieldResolver;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\text\Plugin\Field\FieldType\TextItemBase;
use Drupal\text\Plugin\Field\FieldType\TextWithSummaryItem;
use Drupal\Core\Render\RendererInterface;
use GraphQL\Type\Definition\ResolveInfo;

class TextFieldResolver extends FieldResolverBase {
  public function applies(ContentEntityInterface $entity, $args, $context, ResolveInfo $info) {
    return $entity->hasField($info->fieldName) &&
      in_array($entity->getFieldDefinition($info->fieldName)->getType(), ["text", "text_long", "text_with_summary"]);
  }

  public function resolveRow(TypedDataInterface $item, $definition, $args, $context, ResolveInfo $info) {
    if ($item instanceof TextWithSummaryItem) {
      $viewVar = $item->view([
        "type" => "text_summary_or_trimmed",
        "settings" => [
          "trim_length" => $args["trimLength"] ?? 120,
        ],
      ]);
      return [
        "value" => $item->value,
        "format" => $item->format,
        "processed" => $item->processed,
        "summary_computed" => (string) RendererInterface::render($viewVar),
      ];
    }

    if ($item instanceof TextItemBase) {
      return [
        "value" => $item->value,
        "format" => $item->format,
        "processed" => $item->processed,
      ];
    }
  }
}
