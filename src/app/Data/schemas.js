'use strict'

const { makeExecutableSchema } = require('graphql-tools')
const { gql } = require('apollo-server');

const resolvers = require('./resolvers.js')

// Define our schema using the GraphQL schema language
const typeDefs = gql`
  type People {
    PeopleId: Int!
    PeopleName: String!
    PeopleUsername: String!
  },
  type Query {
    allUsers: [People]
  }
`

module.exports = typeDefs
