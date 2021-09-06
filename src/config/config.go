package config

import (
	"log"
	"sync"

	"github.com/joho/godotenv"
	"github.com/kelseyhightower/envconfig"
)

type Config struct {
	DbUser       string `envconfig:"db_user"`
	DbPassword   string `envconfig:"db_password"`
	DbHost       string `envconfig:"db_host"`
	DbPort       string `envconfig:"db_port"`
	DbName       string `envconfig:"db_name"`
	DbConnection string `envconfig:"db_connection"`

	AppName string `envconfig:"app_name"`
	AppPort string `envconfig:"app_port"`
}

var once sync.Once
var instance Config

func GetConfig() Config {
	once.Do(func() {
		err := godotenv.Load("../.env")
		if err != nil {
			log.Fatalf("Error getting env %v", err.Error())
		}
		err = envconfig.Process("", &instance)
		if err != nil {
			log.Fatal(err.Error())
		}
	})
	return instance
}
