package mysql

import (
	"database/sql"

	"github.com/jabardigitalservice/office-services/src/domain"
)

// Repositories ...
type Repositories struct {
	PeopleRepo domain.PeopleRepository
}

// NewMysqlRepositories will create an object that represent all repos interface
func NewMysqlRepositories(Conn *sql.DB) *Repositories {
	return &Repositories{
		PeopleRepo: NewMysqlPeopleRepository(Conn),
	}
}
