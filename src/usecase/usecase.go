package usecase

import (
	"time"

	"github.com/jabardigitalservice/office-services/src/domain"
	"github.com/jabardigitalservice/office-services/src/repository/mysql"
)

// Usecases ...
type Usecases struct {
	PeopleUcase domain.PeopleUsecase
}

// NewUcase will create an object that represent all usecases interface
func NewUcase(r *mysql.Repositories, timeoutContext time.Duration) *Usecases {
	return &Usecases{
		PeopleUcase: NewPeopleUsecase(r.PeopleRepo, timeoutContext),
	}
}
