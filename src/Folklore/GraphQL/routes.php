<?php

use Illuminate\Http\Request;

$schemaParameterPattern = '/\{\s*graphql\_schema\s*\?\s*\}/';

//GraphiQL
$graphiQL = config('graphql.graphiql', true);
if ($graphiQL) {
    $graphiQLRoute = config('graphql.graphiql.routes', 'graphiql');
    $graphiQLController = config('graphql.graphiql.controller', '\Folklore\GraphQL\GraphQLController@graphiql');
    $router->get($graphiQLRoute, [
        'as' => 'graphql.graphiql',
        'middleware' => config('graphql.graphiql.middleware', []),
        'uses' => $graphiQLController
    ]);
}

$graphqlRoutes = function ($router) use ($schemaParameterPattern) {
    //Get routes from config
    $routes = config('graphql.routes');
    $queryRoute = null;
    $mutationRoute = null;
    if (is_array($routes)) {
        $queryRoute = array_get($routes, 'query', null);
        $mutationRoute = array_get($routes, 'mutation', null);
    } else {
        $queryRoute = $routes;
        $mutationRoute = $routes;
    }

    //Get controllers from config
    $controllers = config('graphql.controllers', '\Folklore\GraphQL\GraphQLController@query');
    $queryController = null;
    $mutationController = null;
    if (is_array($controllers)) {
        $queryController = array_get($controllers, 'query', null);
        $mutationController = array_get($controllers, 'mutation', null);
    } else {
        $queryController = $controllers;
        $mutationController = $controllers;
    }

    //Query
    if ($queryRoute) {
        // Remove optional parameter in Lumen. Instead, creates two routes.
        if (!$router instanceof \Illuminate\Routing\Router &&
            preg_match($schemaParameterPattern, $queryRoute)
        ) {
            $router->get(preg_replace($schemaParameterPattern, '', $queryRoute), array(
                'as' => 'graphql.query',
                'uses' => $queryController
            ));
            $router->get(preg_replace($schemaParameterPattern, '{graphql_schema}', $queryRoute), array(
                'as' => 'graphql.query.with_schema',
                'uses' => $queryController
            ));
            $router->post(preg_replace($schemaParameterPattern, '', $queryRoute), array(
                'as' => 'graphql.query.post',
                'uses' => $queryController
            ));
            $router->post(preg_replace($schemaParameterPattern, '{graphql_schema}', $queryRoute), array(
                'as' => 'graphql.query.post.with_schema',
                'uses' => $queryController
            ));
        } else {
            $router->get($queryRoute, array(
                'as' => 'graphql.query',
                'uses' => $queryController
            ));
            $router->post($queryRoute, array(
                'as' => 'graphql.query.post',
                'uses' => $queryController
            ));
        }
    }

    //Mutation routes (define only if different than query)
    if ($mutationRoute && $mutationRoute !== $queryRoute) {
        // Remove optional parameter in Lumen. Instead, creates two routes.
        if (!$router instanceof \Illuminate\Routing\Router &&
            preg_match($schemaParameterPattern, $mutationRoute)
        ) {
            $router->post(preg_replace($schemaParameterPattern, '', $mutationRoute), array(
                'as' => 'graphql.mutation',
                'uses' => $mutationController
            ));
            $router->post(preg_replace($schemaParameterPattern, '{graphql_schema}', $mutationRoute), array(
                'as' => 'graphql.mutation.with_schema',
                'uses' => $mutationController
            ));
            $router->get(preg_replace($schemaParameterPattern, '', $mutationRoute), array(
                'as' => 'graphql.mutation.get',
                'uses' => $mutationController
            ));
            $router->get(preg_replace($schemaParameterPattern, '{graphql_schema}', $mutationRoute), array(
                'as' => 'graphql.mutation.get.with_schema',
                'uses' => $mutationController
            ));
        } else {
            $router->post($mutationRoute, array(
                'as' => 'graphql.mutation',
                'uses' => $mutationController
            ));
            $router->get($mutationRoute, array(
                'as' => 'graphql.mutation.get',
                'uses' => $mutationController
            ));
        }
    }
};

foreach (config('graphql.prefix') as $prefix) {
    $router->group([
        'prefix' => $prefix,
        'middleware' => config('graphql.middleware', [])
    ], $graphqlRoutes);
}
