# Development guidelines

## Symfony best practices

### Dependency injection & autowiring

The very power of Symfony framework is its dependency container. It permits to get instances of configured classes, in a performant way.
This also ease a lot the usage of dependency injection pattern on all our classes.

Symfony also come with two powerful additions of the container, the autowiring and the autoconfigure.

Autowiring allows classes instances to be automatically loaded once it's
detected on the constructor of a class. With this tool, we can avoid the direct usage of container (best practice in production code).

Autoconfigure on the other hand, as it name imply, automatically configure classes dependencies and tags by also look at constructor and extends/implements.

#### Service yaml usage

The configuration is inside the `services.yaml` file
````yaml
services:
  # default configuration for services in *this* file
  _defaults:
    autowire: true      # Automatically injects dependencies in your services.
    autoconfigure: true # Automatically registers your services as commands, event subscribers, etc.
    bind:
      $projectDir: '%kernel.project_dir%'

  # makes classes in src/ available to be used as services
  # this creates a service per class whose id is the fully-qualified class name
  GtfsService\:
    resource: '../src/'
    exclude:
      - 'Kernel.php'
      - '../src/Shared/{Doctrine/DBAL/Types, Dto, Document, ValueObject, Entity, DependencyInjection}'
      - '../src/GtfsToLegacy/{Entity, DependencyInjection}'
      - '../src/Gtfs/{Entity, Dto, DependencyInjection}'
````

Here we are automatically configure classes on the namespace `GtfsService` with exclusions, so no need to configure them if you're not doing fancy stuff.

Of course, for some needs, you are still allowed to set up classes configuration, like for example this one:
````yaml
  GtfsService\Gtfs\Import\File\Parser\Loader\ImportFileParserLoader:
      class: GtfsService\Gtfs\Import\File\Parser\Loader\ImportFileParserLoader
      calls:
          - loadFileParsers:
              - '@GtfsService\Gtfs\Import\File\Parser\Csv\CsvAgencyImportFileParser'
              - '@GtfsService\Gtfs\Import\File\Parser\Csv\CsvRouteImportFileParser'
````
It tells Symfony's service container, when an instance of `ImportFileParserLoader` has to be injected, the automatic call of method `loadFileParsers` with dependencies listed below has to be made.
This is quite excellent because it means that we don't need to modify the code inside `ImportFileParserLoader` when we want to add a new file parser.

For more on services configuration, I strongly recommend reading the Symfony official documentation.

#### Dependency injection

Everything I presented on configuration is possible, **ONLY** when you're using dependency injection pattern. You **MUST** use it to benefits of all the power of the Symfony framework,
and also true anywhere you're crafting OOP code, it is a good practice.

What it is ?
Well, look at this example.

````php
class SomeService
{
    public function doSomething(Object $object): void
    {
        $objectManager = new ObjectManager();
        
        $objectManager->processThingOnObject($object, 'thing');
    }
}

class SomeOtherService
{
    public function doSomething(Stuff $stuff): void
    {
        $objectManager = new ObjectManager();
        
        $objectManager->processThingOnStuff($stuff, 'thing');
    }
}
````

In this basic example, we have two services classes that are using a `ObjectManager` to do some stuff.
Let's imagine we have 3 or 4 of others classes that use the `ObjectManager`.

For now, it's simple, `ObjectManager` can work on itself, without any dependencies. So we're simply instantiate it with the `new` keyword.

Okay, but now, `ObjectManager` need a logger to work, so it has to be, some when, injected to it.

Now, you're in trouble, because you have to add a logger service on **every** instance of the `ObjectManager`. That means that your PR will contain a lot of changed code, with a lot of risk included, only to add a logger.

So, what if we use the dependency injection pattern instead of ?
````php
class SomeService
{
    public function __construct(private ObjectManager $objectManager){}
    
    public function doSomething(Object $object): void
    {        
        $this->objectManager->processThingOnObject($object, 'thing');
    }
}

class SomeOtherService
{
    public function __construct(private ObjectManager $objectManager){}

    public function doSomething(Stuff $stuff): void
    {        
        $this->objectManager->processThingOnStuff($stuff, 'thing');
    }
}
````

Now, it's easy to see that our services will not need to be modified if we change the way `ObjectManager` has to be instantiated. We strongly set the dependency on them to tell, those classes can't live without an `ObjectManager` instance.

Without the container, we would have to create a builder that instantiate the good services to build the application. On Symfony it's already done, and it needs no effort, so use it!

There are many benefits of using dependency injection instead of manual one, I'll list you some of them:
* No need to modify code that's using a service when you have to change it.
* Testing is easier because you can isolate your tested class by mocking the dependencies.
* You can use [dependency inversion](oop_clean_code.md#the-d-of-solid---dependency-inversion)
* The code is way cleaner because it's not parasited with services managements.
* It can control services internal configuration with `yaml` files.

Of course there are some cases you can't use it, but they are few:
* Everything that are representing data **MUST NOT** be injected via constructor (e.g.: Entities, DTO, ...) to services classes.
* When using classes that cannot be configured via `yaml` files. In this case I strongly recommend creating an intermediary service like a Wrapper or Adapter, to surround that class and configure it in the `yaml` file.

### Use libraries instead of reinventing the wheel

Every application that are made, use common logic, for instance :
* Read/Write on files
* Send mail
* Log
* ...

If you're doing something like that, you **MUST** ask yourself : "Is it great to code it by myself ?".
In most cases the answer is, obviously : **no**.

It's calling **reinventing the wheel**. Why your code, written in days, in the rush of a business needs, will be better than an open source library with a hundred of maintainers, that only focus on what it does ? Think about it.

* Is your code will be more performant ? Absolutely no way. Popular libraries are benchmarked all the day with professional tools, your code is not.
* Is your code will be more secure ? Still a big nope. Popular libraries are strongly looked at on that side and will fix every CSE in a day or two, on your code you'll most likely don't know you have a big security breach.
* You are struggling with a common difficulty ? Of course, and libraries did struggle on the exact same point before and found a better workaround that you'll never find.

If I take back my examples, excellent libraries exists for this:
* Read/Write on files -> Flysystem
* Send mail -> Symfony mailer
* Log -> Monolog

### Services classes organization

All services class **MUST** have a clean naming that is representing what it actually does and respecting :
* [Single responsibility](oop_clean_code.md#the-s-of-solid---single-responsibility)
* [Dependency injection](symfony.md#dependency-injection)

Folder and namespace should represent the domain AND/OR the design pattern/common structure that the class is using.
Some examples :
- ProductRepository => `App\Product\Repository\`, `App\Repository\Product`, `App\OneOfYourDomain\Repository`, ...
- ProductBuilder => `App\Product\Builder\`, `App\Builder\Product`, `App\OneOfYourDomain\Builder`, ...
- GcsFileSystemHandler => `App\FileSystem\Handler\Gcs`, `App\FileSystem\`, `App\OneOfYourDomain\Builder`, ...

You **MUST NOT** use those keywords on class name and folders name :
* Service
* Manager

Also, don't forget to stay cohesive with the rest of the codebase, by looking at was had been done before creating a new folder or new class. If any doubts, speak with your TL or Lead dev.

### The serializer is your best friend

The Symfony's (de)serializer is one of the most powerful components of the framework.

It is :
* Very performant.
* Throwing various errors.
* Usable with a lot of formats (xml, csv, json), IO (json, array, custom object) and configurations (transformation, headers)
* Instantiable manually, so usable in difficult places where you can't rely on service container.

When you're using it in pair with Dto you can do a lot of things very easily :
* Process data from requests
* Build responses for HTTP APIs and views
* Parse data from file or database

Its usage is strongly recommended over any other libraries and custom code.
