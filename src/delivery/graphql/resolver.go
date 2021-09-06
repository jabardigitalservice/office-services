package graphql

import (
	"context"

	"github.com/graphql-go/graphql"

	"github.com/jabardigitalservice/office-services/src/domain"
	"github.com/jabardigitalservice/office-services/src/repository"
)

// PeopleEdge holds information of People edge.
type PeopleEdge struct {
	Node   domain.People
	Cursor string
}

// PeopleResult holds information of people result.
type PeopleResult struct {
	Edges    []PeopleEdge
	PageInfo PageInfo
}

// PageInfo holds information of page info.
type PageInfo struct {
	EndCursor   string
	HasNextPage bool
}

type Resolver interface {
	FetchPeople(params graphql.ResolveParams) (interface{}, error)
}

type resolver struct {
	peopleService domain.PeopleUsecase
}

func (r resolver) FetchPeople(params graphql.ResolveParams) (interface{}, error) {
	ctx := context.Background()
	num := 0
	cursor := ""
	if cursorFromClient, ok := params.Args["after"].(string); ok {
		cursor = cursorFromClient
	}

	if numFromClient, ok := params.Args["first"].(int); ok {
		num = numFromClient
	}

	results, cursorFromService, err := r.peopleService.Fetch(ctx, cursor, int64(num))
	if err != nil {
		return nil, err
	}

	edges := make([]PeopleEdge, len(results))
	for index, result := range results {
		if (result != domain.People{}) {
			edges[index] = PeopleEdge{
				Node:   result,
				Cursor: repository.EncodeCursor(result.PeopleId),
			}
		}
	}

	isHasNextPage := false
	if len(results) > 0 {
		results, _, err := r.peopleService.Fetch(ctx, cursorFromService, int64(1))
		if err != nil {
			return nil, err
		}

		if len(results) > 0 {
			isHasNextPage = true
		}
	}

	return PeopleResult{
		Edges: edges,
		PageInfo: PageInfo{
			EndCursor:   cursorFromService,
			HasNextPage: isHasNextPage,
		},
	}, nil
}

func NewResolver(peopleService domain.PeopleUsecase) Resolver {
	return &resolver{
		peopleService: peopleService,
	}
}
