# Quality and testing tools

Follow the indications on this page to be able to run the tests and the quality tools.

## Quality

### Phpstan
[Phpstan](https://phpstan.org/user-guide/getting-started) is set to max level and is mandatory on the ci. After you did some code you should run it to see if everything
is compliant to the rules.

To execute phpstan run this command :
````
make phpstan-analyse
````

### Php-cs-fix

Cs fixer rules can be checked directly on the project. As this part is also mandatory on the CI, please check your code time to time.

To analyse if some files break rules :
````
make php-cs-check
````

To automatically change files according to rules :
````
make php-cs-fix
````

## Tests

### Regular test run
To run the entire test suite :
````
make test
````

To run the unit test suite :
````
make test-unit
````

To run the functional test suite :
````
make test-functional
````

To test a specific file or test :
````
# run a specific class
make test-specific filter=MyTestClass

# run a specific function
make test-specific filter=myTestFunction

# reset the test database and run a specific class
make test-specific-db filter=MyTestClass

# reset the test database and run a specific function
make test-specific-db filter=myTestFunction
````

### Coverage

To run the test suite with the coverage (files located at `./coverage`)
````
make test-coverage
````

To see the coverage of a specific file or test :
````
# run coverage for a specific class
make test-specific-coverage filter=MyTestClass

# run coverage for a specific function
make test-specific-coverage filter=myTestFunction

# reset the test database and run coverage for a specific class
make test-specific-coverage-db filter=MyTestClass

# reset the test database and run coverage for a specific function
make test-specific-coverage-db filter=myTestFunction
````

### Infection

To run infection and see grades for the entire test suite
````
make test-infection
````

To see the mutation score of a specific file or test :
````
# run infection for a specific class
make test-infection-specific filter=MyTestClass

# run infection for a specific function
make test-infection-specific filter=myTestFunction

# reset the test database and run infection for a specific class
make test-infection-specific-db filter=MyTestClass

# reset the test database and run infection for a specific function
make test-infection-specific-db filter=myTestFunction
````
