<?php

namespace Drupal\simple_graphql\Graphql\FieldResolver;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use GraphQL\Type\Definition\ResolveInfo;

class NodeFieldResolver implements FieldResolverInterface {
  public function applies(ContentEntityInterface $entity, $args, $context, ResolveInfo $info) {
    return $entity instanceof Node &&
      in_array($info->fieldName, ["editUrl", "defaultVersionUrl", "latestVersionUrl", "versionStatus"]);
  }

  public function resolve(ContentEntityInterface $entity, $args, $context, ResolveInfo $info) {
    $updateAccess = $entity->access("update");

    if ($info->fieldName === "versionStatus") {
      if (!$updateAccess) {
        return null;
      }
      if ($entity->in_preview) {
        return "PREVIEW";
      }
      if ($entity->isDefaultRevision() && $entity->status->value) {
        return "PUBLISHED";
      }
      if ($entity->isLatestRevision() && !$entity->status->value) {
        return "DRAFT";
      }
      return "PREVIOUS_REVISION";
    }

    if (in_array($info->fieldName, ["editUrl", "defaultVersionUrl", "latestVersionUrl"])) {
      if ($updateAccess) {
        if ($entity->in_preview) {
          if ($info->fieldName === "editUrl") {
            $o = ["query" => ["uuid" => $entity->uuid()]];
            if ($entity->isNew()) {
              return Url::fromRoute("node.add", ["node_type" => $entity->bundle()], $o)
                ->setAbsolute()
                ->toString();
            } else {
              return $entity
                ->toUrl("edit-form", $o)
                ->setAbsolute()
                ->toString();
            }
          }
        } else {
          if ($info->fieldName === "editUrl") {
            return $entity
              ->toUrl("edit-form")
              ->setAbsolute()
              ->toString();
          }

          if ($info->fieldName === "defaultVersionUrl" && !$entity->isDefaultRevision()) {
            return $entity
              ->toUrl("canonical")
              ->setAbsolute()
              ->toString();
          }

          if ($info->fieldName === "latestVersionUrl" && !$entity->isLatestRevision()) {
            return $entity
              ->toUrl("latest-version")
              ->setAbsolute()
              ->toString();
          }
        }
      }
    }
  }
}
