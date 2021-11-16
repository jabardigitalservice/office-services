# Office Service (SIKD) 

<a href="https://codeclimate.com/github/jabardigitalservice/office-services/maintainability"><img src="https://api.codeclimate.com/v1/badges/888efd380ccef5a509cd/maintainability" /></a>
## Overview
This service is used by [Office Mobile (Flutter)](https://github.com/jabardigitalservice/office-mobile).

## Stack Architechture
1. PHP 8, Laravel
2. MariaDB 5.5
3. GraphQL

## Local development quickstart
Clone the repository
```
$ git clone git@github.com:jabardigitalservice/office-services.git
```

Enter into the `src` directory
```
$ cd src
```
Copy the config file and adjust the needs
```
$ copy .env-example .env
```
Generate the APP_KEY
```
$ php artisan key:generate
```
App dependencies using composer
```
$ composer install
```
DB migration
```
$ php artisan migrate
```

Run the local server:
```
$ php artisan serve
```

Having fun with the playgrond:
```
Open on the browser: {APP_URL}/graphql-playground
```

### Code Style Checking
```
$ ./vendor/bin/phpcs
```

### Unit & Feature Testing
```
$ ./vendor/bin/phpunit
```
