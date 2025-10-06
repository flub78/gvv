# GVV (Gestion Vol à Voile)

## Project Overview

GVV is a web application for managing gliding clubs. It is built with the CodeIgniter 2.1.3 PHP framework and has been in development since 2011. The project was recently migrated from Subversion to Git.

The application is structured as a typical CodeIgniter project, with the following key directories:

*   `application`: Contains the application code, including controllers, models, views, and configuration files.
*   `system`: Contains the CodeIgniter framework files.
*   `assets`: Contains static assets such as CSS, JavaScript, and images.
*   `tests`: Contains the PHPUnit test suites.

## Building and Running

The project uses Ant for building and running various tasks. The `build.xml` file defines the following key targets:

*   `build`: Builds the project by running all the defined tasks, including linting, testing, and generating documentation.
*   `clean`: Cleans up the build artifacts.
*   `lint`: Lints the PHP code.
*   `phpunit`: Runs the PHPUnit test suites.
*   `phpdoc`: Generates API documentation.

To run the application, you will need a web server with PHP and a MySQL database. The database configuration is located in `application/config/database.php`, which is ignored by Git. You will need to create this file based on `application/config/database.php.example` if it exists, or configure it manually.

## Testing

The project has a comprehensive test suite that uses PHPUnit. The `run-all-tests.sh` script provides a convenient way to run all the test suites.

To run the tests, execute the following command:

```bash
./run-all-tests.sh
```

You can also run the tests with code coverage by using the `--coverage` flag:

```bash
./run-all-tests.sh --coverage
```

## Development Conventions

The project follows the coding standards defined in `build/phpcs.xml`. The `phpmd.xml` file defines the rules for the PHP Mess Detector.

The project uses a Gitflow-like branching model. New features should be developed in feature branches and then merged into the `develop` branch. The `master` branch is used for production releases.
