<?php

namespace Drupal\simple_graphql\Graphql\FieldResolver;

use Drupal\Core\Entity\ContentEntityInterface;
use GraphQL\Type\Definition\ResolveInfo;

interface FieldResolverInterface {
  public function applies(ContentEntityInterface $entity, $args, $context, ResolveInfo $info);
  public function resolve(ContentEntityInterface $entity, $args, $context, ResolveInfo $info);
}
