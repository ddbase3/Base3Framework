# Base3Framework

Base3Framework is a lightweight yet powerful PHP framework designed to simplify modern web development. It offers a range of essential features that help developers build scalable, maintainable, and fast applications. The framework includes a variety of components such as a robust autoloader, a service container, dependency injection, microservices support, MVC architecture, plugin functionality, and extensive interfaces for extensibility.

This README will guide you through the installation and usage of the Base3Framework and highlight its unique features.

## Features

* **Autoloader**: Automatically loads classes as needed, eliminating the need to include files manually.
* **Service Container**: Central place for defining and retrieving services and parameters.
* **Dependency Injection**: Provides flexibility in managing object dependencies through inversion of control.
* **Microservices**: Create and consume microservices with support for DI, routing, flags, and receivers.
* **ClassMap**: Maps classes to specific locations and offers utilities for discovery and instantiation.
* **MVC Architecture**: Clean separation of concerns using controller, view, and model logic.
* **Plugin System**: Modular architecture for extending the framework with isolated plugins.
* **Multilingual**: Support for runtime language selection and language-specific views.
* **Event & Hook System**: Register listeners and dispatch events or hooks with prioritization.
* **Page Modules**: Pluggable page components (header, footer, content, etc.) with priorities and dependencies.
* **XRM System**: Extended Relationship Management with tagging, relations, filters, and access control.
* **Worker & Cron System**: Register and schedule background jobs with priorities.
* **Token System**: Secure, scoped tokens for time-limited operations (e.g. password reset, validation).
* **Session & Authentication**: Manage session lifecycle and login/logout functionality.
* **Logging & Configuration**: Central services for storing logs and configuration data.

## Requirements

* PHP 8.1 or higher
* A web server (e.g., Apache or Nginx)

## Installation

### Step 1: Clone the repository

```bash
git clone https://github.com/ddbase3/Base3Framework.git
```

### Step 2: Configuration

Some settings might need to be adjusted to suit your environment. You can configure the application by using the example file in the `cnf` directory. Key settings include database connections, languages, and logging.

## Composer (optional)

If you want to use Composer to manage dependencies in plugins, follow these steps. This setup ensures that Composer is only used within the plugin scope, and not in the core framework.

### 1. Install Dependencies for Plugins

```bash
composer --working-dir=plugin init
```

Example composer.json:

```json
{
    "require": {
        "monolog/monolog": "^3.0",
        "guzzlehttp/guzzle": "^7.0"
    }
}
```

```bash
composer --working-dir=plugin install
```

Optional merging of plugin composer files:

```bash
php setup/merge-composer.php
composer --working-dir=plugin install
```

### 2. Autoloading Composer Dependencies

Composer autoloading is automatically detected inside plugins.

### 3. Using Composer Packages in Your Plugins

Example:

```php
use Monolog\Logger;
use GuzzleHttp\Client;

$logger = new Logger('plugin');
$client = new Client();
```

## Using the Makefile

```bash
make           # Merge all plugin composer.json files and install dependencies
make install   # Same as above
make update    # Merge and update all dependencies
make clean     # Remove merged composer.json and vendor directory
make doc       # Create documentation
```

## Framework Components

### Autoloader

Efficient class loading based on namespaces and class maps.

### Service Container

Provides service registration and lookup. Supports flags like `SHARED`, `NOOVERWRITE`, `ALIAS`, and `PARAMETER`.

### Dependency Injection

Integrated via container or constructor-based injection.

Example:

```php
class UserService {
    private Database $database;

    public function __construct(Database $database) {
        $this->database = $database;
    }

    public function getUserData(int $userId): array {
        return $this->database->fetchUser($userId);
    }
}
```

### Microservice Architecture

Use `IMicroservice`, `IMicroserviceConnector`, `IMicroserviceReceiver`, and `IMicroserviceFlags` for building structured microservice endpoints and consumers.

### MVC & Page System

Pages implement `IPage`, `IPageCatchall`, `IPagePostDataProcessor`. Views and modules use `IMvcView` and `IPageModule*` interfaces.

### Event & Hook System

* Use `IEventManager`, `IStoppableEvent`
* Hook-based extensibility via `IHookListener` and `IHookManager`

### XRM (Extended Relationship Management)

* Entry handling via `IXrm`
* Tagging, allocation, app linking
* Filtering with `IXrmFilterModule`
* Access control and user assignment

### Workers & Cron

Jobs implement `IJob`; cron-capable jobs use `ICron`. Workers implement `IWorker` and orchestrate job execution.

### Token System

Create, check, and delete scoped tokens using `IToken` (e.g. for secure links, confirmation flows).

### Session & Authentication

* `ISession` for checking session state
* `IAuthentication` for login/logout
* `IAccesscontrol` for user ID access

### Language System

Use `ILanguage` to manage and switch between available languages at runtime.

### Logging

Log entries using `ILogger`, with support for scoped log streams.

### Configuration

Manage structured configuration data using `IConfiguration`.

## Example Usage

### Hello World Example

1. Create a Controller:

```php
use Base3\Page\Api\IPage;

class HelloController implements IPage {
    public function getOutput($out = 'html') {
        return '<h1>Hello, World!</h1>';
    }

    public function getName(): string {
        return 'hello';
    }
}
```

2. Register the page module or route in the service selector or router.

3. Visit `/hello.php` and you will see the rendered output.

## 🔌 Available BASE3 Plugins

The BASE3 Framework can be extended through numerous plugins that add modular functionality, visualizations, management tools, and AI-based features. Below is a selection of useful extensions:

### 📊 Data & Visualization

- **[DataHawk](https://github.com/ddbase3/DataHawk)**  
  Interactive reports with configurable tables and charts directly within BASE3 pages.
- **[Vizion](https://github.com/ddbase3/Vizion)**  
  Visual representation of data using bar, pie, line charts, etc., based on DataHawk.

### 🤖 AI & Automation

- **[Chatbot](https://github.com/ddbase3/Chatbot)**  
  Chatbot UI for integration anywhere in a webproject, i.e. as a page module or a bubble.
- **[MissionBay](https://github.com/ddbase3/MissionBay)**  
  Agent-based system for orchestrating autonomous tasks, event-driven communication, and workflows.

### ⚙️ Management & Infrastructure

- **[Base3Manager](https://github.com/ddbase3/Base3Manager)**  
  UI for managing and activating BASE3 plugins.
- **[ManageWebsite](https://github.com/ddbase3/ManageWebsite)**, **[ManageDatabase](https://github.com/ddbase3/ManageDatabase)**, **[ManageGit](https://github.com/ddbase3/ManageGit)**, **[ManageServer](https://github.com/ddbase3/ManageServer)**, **[ManageIlias](https://github.com/ddbase3/ManageIlias)**, and more 
  Specialized manager plugins for system or project-level administration.

### 🛠️ Development Tools

- **[Base3Tools](https://github.com/ddbase3/Base3Tools)**  
  Collection of useful development tools for working with BASE3.
- **[Debugger](https://github.com/ddbase3/Debugger)**, **[IliasDebugger](https://github.com/ddbase3/IliasDebugger)**  
  Advanced debugging plugins – also suitable for ILIAS-based projects.

### 🧩 ILIAS Integration

- **[Base3IliasAdapter](https://github.com/ddbase3/Base3IliasAdapter)**  
  ILIAS UI hook plugin - extensions for deep integration with ILIAS.
- **[ManageBase3Ilias](https://github.com/ddbase3/ManageBase3Ilias)**  
  BASE3 Manager plugin for managing and configure BASE3 components within ILIAS environments.

### 📦 Other Plugins

- **[ModuledPage](https://github.com/ddbase3/ModuledPage)**  
  Modular page elements for structured content presentation.
- **[FreeTemplate](https://github.com/ddbase3/FreeTemplate)**  
  Starter template for custom BASE3 plugins (LGPL).
- **[OpenAiConversation](https://github.com/ddbase3/OpenAiConversation)**  
  Flow-based chatbot with OpenAI integration, suitable for RAG-based applications.

> You can find more plugins at: [github.com/topics/base3-plugin](https://github.com/topics/base3-plugin)

## Contributing

Contributions are welcome. Please follow PSR-12 and document new interfaces clearly.

## Example Projects Using BASE3

* [Mosaic Creator](https://mosaic-creator.de)
* [Contourz Ballet Photography](https://contourz.photo)

## License

Base3Framework is licensed under the GPL 3.0 License. See the LICENSE file for more details.

## Documentation

For more detailed documentation, please visit the official website or check the API reference.

## Links

* [Official project page (documentation)](https://base3.de/crm.php?id=05fdf4d623714e24a7418c160795ef34)

