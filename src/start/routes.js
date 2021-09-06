'use strict'

/*
|--------------------------------------------------------------------------
| Routes
|--------------------------------------------------------------------------
|
| Http routes are entry points to your web application. You can create
| routes for different URLs and bind Controller actions to them.
|
| A complete guide on routing is available here.
| http://adonisjs.com/docs/4.1/routing
|
*/

/** @type {typeof import('@adonisjs/framework/src/Route/Manager')} */
const Route = use('Route')
const { ApolloServer } = require('apollo-server')
const schemas = require('../app/Data/schemas.js');
const resolvers = require('../app/Data/resolvers.js');

Route.get('/', () => {
  return { greeting: 'Hello world in JSON' }
})

Route.route('/graphql', ({ request, response }) => {
  return new ApolloServer({
    schemas,
    resolvers,
  });
}, ['GET', 'POST'])
