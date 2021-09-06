package mysql

import (
	"context"
	"database/sql"

	"github.com/sirupsen/logrus"

	"github.com/jabardigitalservice/office-services/src/domain"
	"github.com/jabardigitalservice/office-services/src/repository"
)

type mysqlPeopleRepository struct {
	Conn *sql.DB
}

// NewMysqlPeopleRepository will create an object that represent the people.Repository interface
func NewMysqlPeopleRepository(Conn *sql.DB) domain.PeopleRepository {
	return &mysqlPeopleRepository{Conn}
}

func (m *mysqlPeopleRepository) fetch(ctx context.Context, query string, args ...interface{}) (result []domain.People, err error) {
	rows, err := m.Conn.QueryContext(ctx, query, args...)
	if err != nil {
		logrus.Error(err)
		return nil, err
	}

	defer func() {
		errRow := rows.Close()
		if errRow != nil {
			logrus.Error(errRow)
		}
	}()

	result = make([]domain.People, 0)
	for rows.Next() {
		t := domain.People{}
		err = rows.Scan(
			&t.PeopleId,
			&t.PeopleName,
			&t.PeopleUsername,
		)

		if err != nil {
			logrus.Error(err)
			return nil, err
		}
		result = append(result, t)
	}

	return result, nil
}

func (m *mysqlPeopleRepository) Fetch(ctx context.Context, cursor string, num int64) (res []domain.People, nextCursor string, err error) {
	query := `
		SELECT PeopleId, PeopleName, PeopleUsername
  		FROM people WHERE PeopleId > ? ORDER BY PeopleId LIMIT ? 
	`

	decodedCursor, err := repository.DecodeCursor(cursor)
	if err != nil && cursor != "" {
		return nil, "", domain.ErrBadParamInput
	}

	res, err = m.fetch(ctx, query, decodedCursor, num)
	if err != nil {
		return nil, "", err
	}

	if len(res) != 0 {
		nextCursor = repository.EncodeCursor(res[len(res)-1].PeopleId)
	}

	return
}
