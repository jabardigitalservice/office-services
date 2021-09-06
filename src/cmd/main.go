package main

import (
	"database/sql"
	"fmt"
	"log"
	"net/url"
	"time"

	_ "github.com/go-sql-driver/mysql"
	"github.com/graphql-go/graphql"
	"github.com/graphql-go/handler"
	"github.com/labstack/echo"
	"github.com/sirupsen/logrus"

	"github.com/jabardigitalservice/office-services/src/config"
	_graphQLPeopleDelivery "github.com/jabardigitalservice/office-services/src/delivery/graphql"
	"github.com/jabardigitalservice/office-services/src/middleware"
	_peopleRepo "github.com/jabardigitalservice/office-services/src/repository/mysql"
	_peopleUcase "github.com/jabardigitalservice/office-services/src/usecase"
)

func init() {
	appName := config.GetConfig().AppName
	if appName == "local" || appName == "development" {
		log.Println("Service RUN on DEBUG mode")
	}
}

func main() {
	dbHost := config.GetConfig().DbHost
	dbPort := config.GetConfig().DbPort
	dbUser := config.GetConfig().DbUser
	dbPass := config.GetConfig().DbPassword
	dbName := config.GetConfig().DbName
	connection := fmt.Sprintf("%s:%s@tcp(%s:%s)/%s", dbUser, dbPass, dbHost, dbPort, dbName)
	val := url.Values{}
	val.Add("parseTime", "1")
	val.Add("loc", "Asia/Jakarta")
	dsn := fmt.Sprintf("%s?%s", connection, val.Encode())
	dbConn, err := sql.Open(`mysql`, dsn)
	if err != nil {
		log.Fatal(err)
	}
	err = dbConn.Ping()
	if err != nil {
		log.Fatal(err)
	}

	defer func() {
		err := dbConn.Close()
		if err != nil {
			log.Fatal(err)
		}
	}()

	e := echo.New()
	middL := middleware.InitMiddleware()
	e.Use(middL.CORS)
	ar := _peopleRepo.NewMysqlPeopleRepository(dbConn)

	timeoutContext := time.Duration(2) * time.Second
	au := _peopleUcase.NewPeopleUsecase(ar, timeoutContext)

	schema := _graphQLPeopleDelivery.NewSchema(_graphQLPeopleDelivery.NewResolver(au))
	graphqlSchema, err := graphql.NewSchema(graphql.SchemaConfig{
		Query: schema.Query(),
	})
	if err != nil {
		logrus.Fatal(err)
	}

	graphQLHandler := handler.New(&handler.Config{
		Schema:   &graphqlSchema,
		GraphiQL: true,
		Pretty:   true,
	})

	e.GET("/graphql", echo.WrapHandler(graphQLHandler))
	e.POST("/graphql", echo.WrapHandler(graphQLHandler))

	log.Fatal(e.Start(":" + config.GetConfig().AppPort))
}
