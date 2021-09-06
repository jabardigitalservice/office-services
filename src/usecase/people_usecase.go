package usecase

import (
	"context"
	"time"

	"github.com/jabardigitalservice/office-services/src/domain"
)

type peopleUsecase struct {
	peopleRepo     domain.PeopleRepository
	contextTimeout time.Duration
}

// NewPeopleUsecase will create new an peopleUsecase object representation of domain.PeopleUsecase interface
func NewPeopleUsecase(a domain.PeopleRepository, timeout time.Duration) domain.PeopleUsecase {
	return &peopleUsecase{
		peopleRepo:     a,
		contextTimeout: timeout,
	}
}

func (a *peopleUsecase) Fetch(c context.Context, cursor string, num int64) (res []domain.People, nextCursor string, err error) {
	if num == 0 {
		num = 10
	}

	ctx, cancel := context.WithTimeout(c, a.contextTimeout)
	defer cancel()

	res, nextCursor, err = a.peopleRepo.Fetch(ctx, cursor, num)
	if err != nil {
		return nil, "", err
	}

	return
}
