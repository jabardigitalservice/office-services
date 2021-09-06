package graphql

import "github.com/graphql-go/graphql"

// PeopleGraphQL holds people information with graphql object
var PeopleGraphQL = graphql.NewObject(
	graphql.ObjectConfig{
		Name: "People",
		Fields: graphql.Fields{
			"peopleId": &graphql.Field{
				Type: graphql.String,
			},
			"peopleName": &graphql.Field{
				Type: graphql.String,
			},
			"PeopleUsername": &graphql.Field{
				Type: graphql.String,
			},
		},
	},
)

// PeopleEdgeGraphQL holds people edge information with graphql object
var PeopleEdgeGraphQL = graphql.NewObject(
	graphql.ObjectConfig{
		Name: "PeopleEdge",
		Fields: graphql.Fields{
			"node": &graphql.Field{
				Type: PeopleGraphQL,
			},
			"cursor": &graphql.Field{
				Type: graphql.String,
			},
		},
	},
)

// PeopleResultGraphQL holds people result information with graphql object
var PeopleResultGraphQL = graphql.NewObject(
	graphql.ObjectConfig{
		Name: "PeopleResult",
		Fields: graphql.Fields{
			"edges": &graphql.Field{
				Type: graphql.NewList(PeopleEdgeGraphQL),
			},
			"pageInfo": &graphql.Field{
				Type: pageInfoGraphQL,
			},
		},
	},
)

var pageInfoGraphQL = graphql.NewObject(
	graphql.ObjectConfig{
		Name: "PageInfo",
		Fields: graphql.Fields{
			"endCursor": &graphql.Field{
				Type: graphql.String,
			},
			"hasNextPage": &graphql.Field{
				Type: graphql.Boolean,
			},
		},
	},
)

// Schema is struct which has method for Query and Mutation. Please init this struct using constructor function.
type Schema struct {
	peopleResolver Resolver
}

// Query initializes config schema query for graphql server.
func (s Schema) Query() *graphql.Object {
	objectConfig := graphql.ObjectConfig{
		Name: "Query",
		Fields: graphql.Fields{
			"FetchPeople": &graphql.Field{
				Type:        PeopleResultGraphQL,
				Description: "Fetch People",
				Args: graphql.FieldConfigArgument{
					"first": &graphql.ArgumentConfig{
						Type: graphql.Int,
					},
					"after": &graphql.ArgumentConfig{
						Type: graphql.String,
					},
				},
				Resolve: s.peopleResolver.FetchPeople,
			},
		},
	}

	return graphql.NewObject(objectConfig)
}

// NewSchema initializes Schema struct which takes resolver as the argument.
func NewSchema(peopleResolver Resolver) Schema {
	return Schema{
		peopleResolver: peopleResolver,
	}
}
