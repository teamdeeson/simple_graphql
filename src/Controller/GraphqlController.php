<?php

namespace Drupal\simple_graphql\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\simple_graphql\Plugin\SchemaPluginManager;
use Drupal\simple_graphql\SchemaInterface;
use GraphQL\Error\DebugFlag;
use GraphQL\Language\Parser;
use GraphQL\Server\ServerConfig;
use GraphQL\Server\StandardServer;
use GraphQL\Utils\AST;
use GraphQL\Utils\BuildSchema;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class GraphqlController extends ControllerBase {
  /*
  protected SchemaPluginManager $pluginManager;
  protected CacheBackendInterface $cache;
  */
  protected $pluginManager;
  protected $cache;

  public function __construct(SchemaPluginManager $pluginManager, CacheBackendInterface $cache) {
    $this->pluginManager = $pluginManager;
    $this->cache = $cache;
  }

  public static function create(ContainerInterface $container) {
    return new static($container->get("plugin.manager.simple_graphql.schema"), $container->get("cache.default"));
  }

  public function graphql(string $schema, ServerRequestInterface $request) {
    /** @var SchemaInterface */
    $plugin = $this->pluginManager->createInstance($schema);
    $definition = $this->pluginManager->getDefinition($schema);

    $accept = $request->getHeaderLine("accept");

    if (strpos($accept, "text/html") !== false) {
      return new Response($this->graphiql($definition["path"]));
    }

    $serverConfig = new ServerConfig();

    $serverConfig->setSchema($this->getSchema($schema, $definition, $plugin));

    $serverConfig->setErrorsHandler(function (array $errors, callable $formatter) {
      foreach ($errors as $error) {
        watchdog_exception("simple_graphql", $error);
      }
      return array_map($formatter, $errors);
    });
    $serverConfig->setDebugFlag(DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE);
    $serverConfig->setQueryBatching(true);
    $serverConfig->setContext(["pluginId" => $definition["id"]]);

    $decoratedServerConfig = $plugin->configureServer($serverConfig);

    $server = new StandardServer($decoratedServerConfig);

    // TODO persist queries.
    // 'persistentQueryLoader' => function($queryId, $params) {
    //   $c = $this->cache->get('simple_graphql.persisted_query.' . $queryId);
    //   if ($c === FALSE) {
    //     throw new RequestError('PersistedQueryNotFound');
    //   }
    //   return $c->data;
    // }

    if (stripos($request->getHeaderLine("content-type"), "application/json") !== false) {
      $input = Json::decode($request->getBody()->getContents());
      // $this->persistQueries($input);
      $request = $request->withParsedBody($input);
    }

    $output = $server->executePsrRequest($request);
    return new JsonResponse($output);
  }

  // TODO
  // public function persistQueries($input) {
  //   if (!is_array($input)) {
  //     $input = [$input];
  //   }
  //   foreach ($input as $i) {
  //     if (!empty($i["query"]) && !empty($i["extensions"]["persistedQuery"]["sha256Hash"])) {
  //       $hash = hash("sha256", $i["query"]);
  //       $this->cache()->set("simple_graphql.persisted_query." . $hash, $i["query"]);
  //     }
  //   }
  // }

  public function getSchema($pluginId, $definition, $plugin) {
    $key = "simple_graphql.schema." . $pluginId;
    if ($c = $this->cache->get($key) && false) {
      $doc = AST::fromArray($c->data);
    } else {
      $path = DRUPAL_ROOT . "/" . drupal_get_path("module", $definition["provider"]) . "/" . $definition["schemaFile"];
      $doc = Parser::parse(file_get_contents($path));
      $this->cache->set($key, AST::toArray($doc));
    }
    return BuildSchema::build($doc, [$plugin, "schemaTypeConfigDecorator"]);
  }

  public function graphiql($path) {
    return <<<HTML
      <!DOCTYPE html>
      <html>
        <head>
          <title>Graphiql</title>
          <link href="https://unpkg.com/graphiql/graphiql.min.css" rel="stylesheet" />
        </head>
        <body style="margin: 0;">
          <div id="graphiql" style="height: 100vh;"></div>

          <script
            crossorigin
            src="https://unpkg.com/react/umd/react.production.min.js"
          ></script>
          <script
            crossorigin
            src="https://unpkg.com/react-dom/umd/react-dom.production.min.js"
          ></script>
          <script
            crossorigin
            src="https://unpkg.com/graphiql/graphiql.min.js"
          ></script>

          <script>
            const graphQLFetcher = graphQLParams =>
              fetch('{$path}', {
                method: 'post',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(graphQLParams),
              })
                .then(response => response.json())
                .catch(() => response.text());
            ReactDOM.render(
              React.createElement(GraphiQL, { fetcher: graphQLFetcher }),
              document.getElementById('graphiql'),
            );
          </script>
        </body>
      </html>
    HTML;
  }
}
