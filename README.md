# Base3Framework

Base3Framework is a lightweight yet powerful PHP framework designed to simplify modern web development. It offers a range of essential features that will help developers build scalable, maintainable, and fast applications. The framework includes a variety of components such as a robust autoloader, a service locator container, dependency injection, microservices support, MVC architecture, and plugin functionality.

This README will guide you through the installation and usage of the Base3Framework and highlight its unique features.

## Features

- **Autoloader**: Automatically loads classes as needed, eliminating the need to include files manually.
- **Service Locator Container**: Simplifies access to application services and dependencies.
- **Dependency Injection**: Provides flexibility in managing object dependencies through inversion of control.
- **Microservices**: Easily handle microservices with support for DI integration.
- **ClassMap**: Maps classes to specific locations and offers special functions for efficient management.
- **MVC Architecture**: Supports the MVC (Model-View-Controller) design pattern for a structured application.
- **Plugin Support**: Easily extend and customize your application with plugins.
- **Multilingual**: All base features for supporting application with multiple languages.

## Requirements

- PHP 7.0 or higher
- A web server (e.g., Apache or Nginx)

## Installation

### Step 1: Clone the repository

First, clone the Base3Framework repository to your local development environment:

```bash
git clone https://github.com/ddbase3/Base3Framework.git
```

### Step 2: Configuration

Some settings might need to be adjusted to suit your environment. You can configure the application by using the example file in the cnf directory. Key settings include database connections, languages, logging.

## Framework Components

### Autoloader

Base3Framework uses an efficient autoloader that ensures classes are loaded only when needed. It ensures compatibility with other modern PHP libraries and frameworks.

You don't need to manually include class files. Simply instantiate the class, and the autoloader will take care of the rest.

### Service Locator Container

The service locator container is a central part of the framework's architecture. It allows easy access to services and objects needed throughout the application. By using the service locator, you can retrieve services in a clean and organized manner.

Example:

```php
$serviceLocator = new \Base3\Core\ServiceLocator();
$myService = $serviceLocator->get('MyService');
```

### Dependency Injection (DI)

Dependency Injection in Base3Framework helps to decouple your code by injecting dependencies instead of creating them directly within classes. This allows for easier unit testing and cleaner, more maintainable code. This easily combines with the ServiceLocator architecture.

Example:

```php
class UserService
{
    private $database;

    public function __construct(Database $database) {
        $this->database = $database;
    }

    public function getUserData($userId) {
        return $this->database->fetchUser($userId);
    }
}
```

### Microservices Support

Base3Framework makes it easy to create and manage microservices. You can use the dependency injection container to inject the appropriate services for each microservice, allowing for a modular, scalable application structure.

### ClassMap

Base3Framework supports a class map, which provides an efficient way to map classes to specific files for easy loading and access. This is particularly useful for large applications with many classes, ensuring faster class resolution.

### MVC Architecture

Base3Framework follows the popular MVC (Model-View-Controller) pattern, allowing you to organize your code into three main parts:

- **Model**: Represents the application's data and business logic.
- **View**: Displays the data to the user.
- **Controller**: Handles user input, interacts with the model, and updates the view.

The framework provides basic controllers, views, and models for building applications, but you can extend and customize them according to your needs.

### Plugin System

Base3Framework offers a plugin system that allows you to extend the functionality of your application easily. Plugins can be loaded and configured independently, providing a flexible way to add features like user authentication, payment gateways, or third-party integrations.

Plugins automatically integrate with the Autoloader, the ServiceLocator and the DependencyInjection.

### Multilingual

## Example Usage

### Hello World Example

Here's a simple example to demonstrate how to use Base3Framework with MVC:

1. Create a Controller:
```php
// TODO
```

2. Create a View file:
```php
// TODO
```

When you visit /hello.php in the browser, you should see the message "Hello, World!".

### Contributing

### License

Base3Framework is licensed under the LGPL License. See the LICENSE file for more details.

## Documentation

For more detailed documentation, please visit the official website or check the API reference.

