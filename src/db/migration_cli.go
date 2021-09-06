package main

import (
	"database/sql"
	"log"
	"os"
	"strconv"
	"time"

	_ "github.com/go-sql-driver/mysql"
	"github.com/joho/godotenv"
	"github.com/qustavo/dotsql"
	migrate "github.com/rubenv/sql-migrate"
	"github.com/sirupsen/logrus"
	"github.com/urfave/cli"

	"github.com/jabardigitalservice/office-services/src/config"
)

func main() {
	err := godotenv.Load("../.env")
	if err != nil {
		log.Fatalf("Error getting env %v", err.Error())
	}

	app := cli.NewApp()
	app.Name = "Migrations cli"
	app.Usage = "Migration db"
	app.Version = "1.0.0"

	createFlags := []cli.Flag{
		cli.StringFlag{
			Name:  "name",
			Value: "migration_example",
		},
	}

	migrationFlags := []cli.Flag{
		cli.IntFlag{
			Name:  "step",
			Value: 0,
		},
	}

	seedFlags := []cli.Flag{
		cli.StringFlag{
			Name:  "table",
			Value: "all",
		},
	}

	db := prepareMySQLDB()
	defer db.Close()

	app.Commands = []cli.Command{
		{
			Name:  "create",
			Usage: "./migration_cli create --name <file_name> / *default name=migration_example",
			Flags: createFlags,
			Action: func(c *cli.Context) error {
				t := time.Now()
				dir := "./db/migrations/"
				timestamp := t.Format("20060102150405")

				f, err := os.Create(dir + timestamp + "_" + c.String("name") + ".sql")
				if err != nil {
					log.Fatal(err)
				}

				content := "-- +migrate Up\n\n" +
					"-- +migrate Down"

				l, err := f.WriteString(content)

				if err != nil {
					log.Fatal(err)
					f.Close()
				}

				logrus.Info("(" + strconv.Itoa(l) + " bytes) migration succeeded")

				err = f.Close()
				if err != nil {
					log.Fatal(err)
				}

				return nil
			},
		},
		{
			Name:  "migrate",
			Usage: "./migration_cli migrate OR ./migration_cli migrate --step <migration_step>",
			Flags: migrationFlags,
			Action: func(c *cli.Context) error {

				n, err := executeDB(db, migrate.Up, c.Int("step"))
				if err != nil {
					logrus.Error("Error migration file: " + err.Error())
				}
				logrus.Info("Applied " + strconv.Itoa(n) + " migrations..")

				return nil
			},
		},
		{
			Name:  "rollback",
			Usage: "./migration_cli rollback OR ./migration_cli rollback --step <rollback_step>",
			Flags: migrationFlags,
			Action: func(c *cli.Context) error {

				n, err := executeDB(db, migrate.Down, c.Int("step"))
				if err != nil {
					logrus.Error("Error: " + err.Error())
				}
				logrus.Info("Rollback " + strconv.Itoa(n) + " migrations..")

				return nil
			},
		},
		{
			Name:  "seed",
			Usage: "./migration_cli seed OR ./migration_cli seed --table <table_name>",
			Flags: seedFlags,
			Action: func(c *cli.Context) error {

				dotSeeder, err := dotsql.LoadFromFile("./db/seeds/master_data.sql")
				if err != nil {
					logrus.Error("Error seeder file: " + err.Error())
				}

				tableName := c.String("table")
				if tableName == "all" {
					for key := range dotSeeder.QueryMap() {
						if key != "check-table-existence" {
							seedTable(key, dotSeeder, db)
						}
					}
				} else {
					seedTable(tableName, dotSeeder, db)
				}

				return nil
			},
		},
	}

	err = app.Run(os.Args)
	if err != nil {
		log.Fatal(err)
	}

}

func prepareMySQLDB() (db *sql.DB) {
	dsn := config.GetConfig().DbUser + ":" + config.GetConfig().DbPassword + "@tcp(" + config.GetConfig().DbHost + ":" + config.GetConfig().DbPort + ")/" + config.GetConfig().DbName + "?parseTime=true"

	db, err := sql.Open(config.GetConfig().DbConnection, dsn)
	if err != nil {
		logrus.Error("Error mysql connection: " + err.Error())
	}

	return db
}

func executeDB(db *sql.DB, direction migrate.MigrationDirection, steps int) (int, error) {
	migrations := &migrate.FileMigrationSource{
		Dir: "./db/migrations",
	}

	n, err := migrate.ExecMax(db, "mysql", migrations, direction, steps)
	if err != nil {
		logrus.Error("Error migration: " + err.Error())
	}

	return n, err
}

func isTableExist(tableName string, dot *dotsql.DotSql, db *sql.DB) bool {
	row, err := dot.QueryRow(db, "check-table-existence", tableName)
	if err != nil {
		log.Fatal(err)
	}

	var exist int
	err = row.Scan(&exist)
	if err != nil {
		log.Fatal(err)
	}
	if exist == 1 {
		return true
	}

	return false
}

func seedTable(tableName string, dot *dotsql.DotSql, db *sql.DB) {

	if !isTableExist(tableName, dot, db) {
		logrus.Error("Table ", tableName, " not exist")
	} else {
		res, err := dot.Exec(db, tableName)
		if err != nil {
			logrus.Error("Error seeder ", tableName, ": ", err.Error())
		} else {
			row, _ := res.RowsAffected()
			logrus.Info("Success seed ", tableName, " : ", row, " rows inserted")
		}
	}
}
