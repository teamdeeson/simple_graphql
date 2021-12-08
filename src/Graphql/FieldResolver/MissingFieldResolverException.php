<?php

namespace Drupal\simple_graphql\Graphql\FieldResolver;

use Drupal\Core\Entity\ContentEntityInterface;
use GraphQL\Type\Definition\ResolveInfo;

class MissingFieldResolverException extends \Exception {
  public function __construct(ContentEntityInterface $entity, ResolveInfo $info) {
    return parent::__construct(
      "No field resolver is available for field \"{$info->fieldName}\" on entity type \"{$entity->getEntityTypeId()}\"",
    );
  }
}
