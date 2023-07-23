# This is a backend test from ^fram

Techs stack:
- PHP 8
- MySQL

## Setup project

Run `composer install` to setup packages

Run `cp .env.test .env`, and change variables in .env file as your desired.

Run `cp init.sql.test init.sql`, and change __your_database_name__, __your_mysql_user__, __your_mysql_password__ in init.sql file as your desired, remember it must the same as variables in your .env file

Run `docker-compose up -d --build`, wait for few minutes and after that access http://localhost:8000

## Example api


- POST http://localhost:8000

data:
```
{
"Pete": "Nick",
"Barbara": "Nick",
"Nick": "Sophie",
"Sophie": "Jonas",
"Jonas": "Giang",
"Vincent": "",
"": "Vincent"
}
```

- GET http://localhost:8000?query=hierarchy
- GET http://localhost:8000?employee=Pete&query=supervisor