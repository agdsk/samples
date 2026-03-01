---
apply: always
---

### Project Guidelines

#### Architecture & Design

- **PHP**: The project uses PHP 5.6.40.
- **CodeIgniter Framework**: The project is built on CodeIgniter 2.1.4.
- **Legacy Code**: A lot of legacy code does not follow the guidelines in this document and should be updated to the current standards whenever a method is modified otherwise.
- **Routing**:
    - Links to controller actions MUST use `urlController()`.
    - The first parameter MUST be a controller class constant, e.g. `counts::class`.
    - The second parameter is the controller method name as a string. It is optional. If excluded, the router will assume `index`.
    - The second or third parameter MAY be an array of key-value pairs that will render as URL query string parameters.
    - e.g. `urlController(counts::class, 'edit', ['id' => 12, 'mode' => 'simple'])`
- **Dependency Injection**:
    - The trait at `updash\traits\UsesModels` allows any class to access model methods and other dependencies.
    - Controllers, Models, Commands, Services, Tasks and any other class can access model methods in a consistent fashion.
        - Classes other than Controllers and Models that use this trait SHOULD execute `$this->enableUpdashCompatability()` in their constructor.
            - This will boot CodeIgniter and load the magic getters for the model layer in the class.
            - This is always safe to do, though it is often unnecessary if CodeIgniter happens to have already been booted when the class is instantiated.
- **Controllers**:
    - MUST extend `base_controller`.
    - Render output with the `render*` methods:
        - `renderApiException()`: Renders an exception as an API error.
        - `renderApiFailure()`: Returns a generic API failure response.
        - `renderApiInvalidApiKey()`: Returns an invalid API key response.
        - `renderApiNotAuthorized()`: Returns an unauthorized API response.
        - `renderApiNotFound()`: Returns a 404 API response.
        - `renderApiSuccess()`: Returns a successful API response with data.
        - `renderDownloadCsv()`: Forces a CSV file download.
        - `renderEmail()`: Renders and returns an email.
        - `renderJson()`: Return a JSON response.
        - `renderJsonEarly()`: Return a JSON response early, but continue processing the request.
        - `renderJsonLastValidationErrors()`: Renders the last validation errors as JSON.
        - `renderJsonValidationErrors()`: Renders specific validation errors as JSON.
        - `renderLayout()`: To render an entire page with layout including a view.
        - `renderText()`: Renders plain text.
        - `renderTextError()`: Renders plain text and returns a 500 response code.
        - `renderTextNotFound()`: Renders plain text and returns a 404 response code.
        - `renderTextSuccess()`: Renders plain text and returns a 200 response code.
        - `renderView()`: Render only a single view.
        - `renderViewError()`: Standard 500 Server Error page.
        - `renderViewException()`: To render a default error message assembled from an exception.
        - `renderViewForbidden()`: Standard 403 Forbidden page.
        - `renderViewNotFound()`: Standard 404 Not Found page.
    - Redirect the user with the `redirect*` methods:
        - `redirect()`: Redirect the user to a controller action. Uses the same format as `urlController()`.
            - e.g. `redirect(counts::class, 'edit', ['id' => 12])`
        - `redirectBack()`: Redirect the user back to the value of `$_SERVER['HTTP_REFERER']`.
        - `redirectRaw()`: Redirect to a specific URL.
    - Validate input parameters with the `validate*` methods:
        - `validate()`: Uses `rakit/validation` (https://github.com/rakit/validation). Accepts an array of parameters as a first argument and an array of validation rules as a second.
        - `validatePost()`: Helper to validate `$_POST` parameters.
        - `validateGet()`: Helper to validate `$_GET` parameters.
    - Flash messages store one-time use messages in Redis for authenticated users across requests that will be displayed the next time a layout is rendered for the user.
        - `flashError()`: Flash an error message.
        - `flashSuccess()`: Flash a success message.
        - `flashValidation()`: Flash a validation errors array, which will display a formatted message to the user about validation errors.
- **Models**:
    - MUST extend `base_model`.
- **Exceptions**: Use custom exception classes located in `application/exceptions/` for error handling.
    - NEVER throw PHP SPL exceptions except `\RuntimeException`.
    - ALWAYS throw an exception that extends `updash\exceptions\ApplicationException`.
    - `ApplicationException` automatically records and reports thrown exceptions to the `error_handler`.
    - This behavior is configurable per exception class (e.g., by setting `$this->sendToErrorHandler = false`).
    - When possible, methods that throw several different kinds of exceptions should have all `\Exception` classes caught in a single catch block and rethrown as a promoted exception like `ServiceRequestFailed`.
- **Queries**:
    - CodeIgniter: Do NOT use the bundled CodeIgniter class, except for the `escape()` method. All queries should make use of the functions in `application/models/common_model.php`.
    - Schema: A copy of the database schema is located in `dev/schema.sql`.
    - Escaping: Use `$this->db->escape()` for all query parameters to prevent SQL injection. This function automatically applies single quotes around the input when necessary.
    - Fetching multiple records: Use `$this->common_model->array_result_assoc($sql)` to fetch multiple records as an associative array. This method returns `false` if no records are found.
    - Fetching a single record: Use `$this->common_model->single_result_assoc($sql)` to fetch a single record as an associative array. This method returns `false` if no record is found.
    - Insert/Update: Use `mysql_insert()`, `mysql_update()`, or `mysql_insert_on_duplicate_key_update()` from `common_model`.
    - Transactions: Use `transaction_start()` and `transaction_commit()` from `common_model`.
- **Helpers**: Global helper functions are located in `application/helpers/`.
    - These functions can be called from anywhere, including views.
    - Helper functions MUST always be prefixed consistently to match their filename (e.g., functions in `memory_helper.php` should start with `memory`).
    - The file `application/helpers/common_helper.php` contains legacy helper functions, many of which could be renamed and moved to other files
- **Commands**: New commands should extend `updash\types\Command\CommandAbstract` and implement the `execute()` method.
- **Tasks**: Long-running scripts should extend `updash\types\Task\TaskAbstract`.
    - Implement `main()` for the core logic.
    - Use `$configLoop = true` for tasks that should run perpetually.
- **APIs**: Classes for external services should be placed in `application/apis/`.
    - Use the `fetch*` and `push*` method naming conventions.
- **Events**: Use `event_handler::emit($eventName, $data)` to trigger application events.
    - Listeners are registered in `event_handler::registerEventListeners()`.
- **Middleware**: Used for cross-cutting concerns like authentication and request validation.
    - Declare middleware using the `@uses` tag in the DocBlock of a Controller class or method.
    - Example: `@uses \updash\types\Middleware\MiddlewareMustBeAuthenticated`
    - Middleware is executed by `updash\services\Middleware` before the controller method is called.
    - Class-level middleware runs before method-level middleware.
    - **Common Middleware**:
        - `MiddlewareMustBeAuthenticated`: Ensures user is logged in.
        - `MiddlewareCanBeUnuthenticated`: Explicitly allows guest access.
        - `MiddlewareMustBeAdmin`: Restricts access to administrators.
        - `MiddlewareHttpGet` / `MiddlewareHttpPost`: Enforces HTTP method.
    - **Custom Middleware**:
        - New middleware should be placed in `application/types/Middleware/`.
        - Should extend `updash\types\Middleware\MiddlewareAbstract` and implement `handle()`.
        - Use `exit;` within `handle()` if the request should be terminated (e.g., after rendering an error page).
- **Traits for Dependency Injection**: Use the `UsesModels` trait in classes that need access to CodeIgniter models. This provides compatibility when running outside the full CI request lifecycle.
- **Debugging**: Use the `updash\traits\Debugger` trait for logging.
    - Call `$this->debug($message)` to log messages.
    - Debug messages are automatically handled based on environment (printed in CLI, sent to Redis for broadcasting, or stored in internal log).
- **Multithreading**: Use the `updash\traits\Multithreading` trait for forking child processes in CLI scripts.
    - Use `$this->forkProcess()` to fork.
    - Use `$this->waitForAllChildren()` or `$this->waitForSpareChild()` to manage child processes.

#### Testing

- **Base Test Class**: All tests should extend `updash\types\TestCase`.
- **Test Execution**: Run tests using the custom bootstrap:
  ```bash
  vendor/bin/phpunit --bootstrap phpunit.php tests/path/to/Test.php
  ```
- **Database Cleanup**: Tests that modify the database should ideally clean up after themselves (e.g., in `tearDown()`) or use unique identifiers to avoid collisions.
- **Authentication in Tests**: Use helper methods like `authenticateAsAaron()` or `authenticateAsUser($id)` in `TestCaseUpdash` for testing protected functionality.

### Entry Points

All executable entry points for the application are located in the project root:

- **/index.php**: Web application entry point.
- **/cli.php**: CLI scripts.
- **/twilio.php**: Twilio webhook endpoint.
- **/phpunit.php**: PHPUnit bootstrap file for running tests.

#### File Structure

- `application/apis/`: API classes for working with remote services.
- `application/commands/`: Commands, which are bundles of business logic.
- `application/config/`: CodeIgniter configuration.
- `application/controllers/`: CodeIgniter Controllers.
- `application/errors/`: CodeIgniter error views.
- `application/exceptions/`: Exception classes.
- `application/handlers/`: Event handling logic.
- `application/helpers/`: Global PHP functions usable anywhere, including views.
- `application/implementations/`: Concrete implementations of third party classes.
- `application/libraries/`: Legacy CodeIgniter classes.
- `application/models/`: CodeIgniter Models.
- `application/scss/`: SCSS.
- `application/services/`: Application services.
- `application/tasks/`: Tasks, which are always-running CLI scripts for maintenance and batch processing.
- `application/traits/`: Traits.
- `application/types/`: PSR-4 like classes that aren't standard CI Models/Controllers.
- `common/`: Legacy junk drawer.
- `dev/`: Developer tools.
- `packages/`: Third party packages not managed by Composer.
- `public/`: Public assets.
- `scripts/`: CLI scripts and maintenance tasks.
- `scripts/php/`: PHP scripts.
- `scripts/python/`: Python scripts.
- `system/`: CodeIgniter core files.
- `tests/application/`: Mirror the application structure for unit and integration tests.
- `updashtwilio/`: Twilio endpoints.
- `vendor/`: Composer dependencies.

### Symbol Naming Conventions

* `camelCase` for variable names
    * ✅️ `$firstName`
    * ⛔️ `$first_name`
* `snake_case` for public controller method names, as these are called via URL
    * ✅️ `get_campaign`
    * ⛔️ `getCampaign`
    * ⛔️ `GetCampaign`
* `camelCase` for method names
    * ✅️ `getFirstName()`
    * ⛔️ `get_first_name()`
* `PascalCase` for class names
    * ✅️ `FirstName`
    * ⛔️ `firstName`
    * ⛔️ `first_name`
    * Controllers and Models are exceptions to this rule, as they must be named according to CodeIgniter conventions
* `UPPER_SNAKE_CASE` for constants
    * ✅️ `MAX_FILE_SIZE`
    * ⛔️ `MaxFileSize`
    * ⛔️ `max_file_size`

### Variable Name Conventions

* IDs (primary key values or foreign key values) should be named with an "Id" suffix
    * ✅️ `$campaignId`
    * ⛔️ `$campaign_id`
    * ⛔️ `$campaignID`
    * ⛔️ `$campaign`
* Keyids (md5 hashed keys) should be named with a "Key" suffix
    * ✅️ `$responseKey`
    * ⛔️ `$response_key`
    * ⛔️ `$responseId`
    * ⛔️ `$response`
* Arrays should be pluralized
    * ✅️ `$campaigns`
    * ✅️ `$campaignIds`
    * ⛔️ `$campaignList`
    * ⛔️ `$allCampaigns`
* Strings containing CSV data should be named with a "Csv" suffix
    * ✅️ `$campaignIdsCsv`
    * ⛔️ `$campaignIds`
    * ⛔️ `$campaignIdsString`
    * ⛔️ `$campaigns`
* File handles should be named `$fh` or prefixed with `$fh`
    * ✅️ `$fh`
    * ✅️ `$fhExportedCsv`
    * ⛔️ `$file`
    * ⛔️ `$handle`
* Curl handles should be named `$ch` or prefixed with `$ch`
    * ✅️ `$ch`
    * ✅️ `$chApiRequest`
    * ⛔️ `$curl`
    * ⛔️ `$handle`

### Reserved Variable Names

Certain variable names are reserved for specific uses to maintain consistency across the codebase:

* `$row`: Should refer to a single database record or query result.
* `$rows`: Should refer to an array of database records or query results.
* `$result`: Should refer to the MySQL result object returned by a query.
* `$data`: Should refer to an associative array of data, often used for passing data to views or methods.
* `$params`: Should refer to an associative array of parameters, often used for method arguments or configurations.

### Model Method Conventions

* `get[Type]`
    * Get a single [Type] record, by Id
* `get[Types]s`
    * Get an array of [Type] records
* `get[Types]Identifiers`
    * Get an array of identifiers (Id, Key, Name, etc) for [Type] records
* `get[Type]By[Field]`
    * Get a single [Type] record by a specific field
    * If Field is an Id, do not include the "Id" suffix in the method name
    * ✅️ `getLeadByResponse()`
    * ⛔️ `getLeadByResponseId()`
* `get[Type]sBy[Field]`
    * Get an array of [Type] records by a specific field
    * If Field is an Id, do not include the "Id" suffix in the method name
    * ✅️ `getLeadByCampaign($campaignId)`
    * ⛔️ `getLeadByCampaignId($campaignId)`
        * If Field is an md5 Keyid, call it "Key" instead of "Keyid"
    * ✅️ `getLeadByResponseKey($responseKey)`
    * ⛔️ `getLeadByResponseKeyid($responseKey)`

### Method Names: Prefixes

Try to always prefix a method name, especially in models, with one of these verbs.

#### Method Names: Prefixes: CRUD Operations

* `count[Type]*`
    * Count operations
* `create[Type]*`
    * Create a new [Type] record
* `exists[Type]*`
    * Check if a record exists, by Id
* `get[Type]*`
    * Get
* `delete[Type]*`
    * Remove a resource
* `update[Type]*`
    * Update a resource

#### Method Names: Prefixes: Import/Export

* `export()`
    * Copy data to a file, external system, etc. from the database
* `import()`
    * Copy data from a file, external system, etc. to the database

#### Method Names: Prefixes: Cache

* `cacheKey*`
* `cacheGet*`
* `cacheSet*`
* `cacheRemove*`
* `cacheWarm*`

### Method Names: Prefixes: Remote Systems

* `fetch*`
    * Retrieve data from an external system or API
* `push*`
    * Send data to an external system or API

#### Method Names: Prefixes: Other

* `debug*`
    * Generates a debug message
* `generate*`
    * Generate a string, XML, JSON or other payload
    * Generate a file on disk, returning the filepath or File object
* `notify*`
    * Send an email, push notification, Slack message or other notification
* `sanitize*`
    * Returns the input after sanitizing it for safe use.
    * Returns false if the input is invalid.

#### Method Names: Prefixes: Resource locking

* `lock*`
    * lock a resource for exclusive use
* `unlock*`
    * Unlock a resource

#### Method Names: Prefixes: Identifiers

* `key*`
    * Returns a key, token, hash or unique identifier
    * Redis key names
* `directory*`
    * Returns a directory path
* `filename*`
    * Returns a filename
* `filepath*`
    * Returns a filepath
* `friendly*`
    * Subject to a friendly format (e.g. friendlyDate, friendlySize)
* `url*`
    * Returns a URL

#### Method Names: Prefixes: Misc

* `execute()`, `main()`, `run()`
    * Execute the main function of a script or class
    * `main()` is used in tasks
    * `execute()` is used in commands
    * `run()` is used in scripts
* `handle*`
    * Generic handler for a an event or dataset.
    * Very generic. Ask yourself if you really need this
* `process*`
    * Generic processor for a dataset.
    * Very generic. Ask yourself if you really need this

### Method Names: Booleans

Methods that return boolean values should be named to clearly indicate a true/false response.

* Form: `[subject][Predicate]()`
    * Example Subject: `campaign`, `client`, `user`
    * Allowed Predicates: `has`, `is`, `can`, `should`, `was`, `supports`, `allows`
* Examples
    * `campaignHasClient()`
    * `campaignIsActive()`
        * `clientHasActiveContract()`
        * `userCanViewCampaign()`
* Rules
    * Keep predicates positive and crisp (`campaignIsActive()`, not `campaignIsNotInactive()`).
    * If a method is clearly about relationships, prefer Has: `campaignHasClient($clientId)`.
    * If it’s policy/permission, prefer Can/Allows: `userCanEditCampaign($userId, $campaignId)`.
    * If it’s derived state, prefer Is: `campaignIsExpired($campaignId)`.