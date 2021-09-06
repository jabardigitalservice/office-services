'use strict'

const People = use('App/Models/People')

// Define resolvers
const resolvers = {
  Query: {
    // Fetch all peoples
    async allUsers() {
      const peoples = await People.all()
      return peoples.toJSON()
    }
  },
}

module.exports = resolvers
