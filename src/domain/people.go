package domain

import (
	"context"
)

// People ...
type People struct {
	PeopleId       int64  `json:"peopleId"`
	PeopleName     string `json:"peopleName" validate:"required"`
	PeopleUsername string `json:"peopleUsername" validate:"required"`
}

// UserUsecase represent the user's usecases
type PeopleUsecase interface {
	Fetch(ctx context.Context, cursor string, num int64) ([]People, string, error)
}

// UserRepository represent the user's repository contract
type PeopleRepository interface {
	Fetch(ctx context.Context, cursor string, num int64) (res []People, nextCursor string, err error)
}
