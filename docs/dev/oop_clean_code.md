# Development guidelines

## Object oriented programming & clean coding

OOP **IS** hard if you don't want to have a messy project at the end. You need to be rigorous and think a lot before coding.
Hopefully, some great mentors of software architecture purposed rules to follow and apply to help us write top-notch code quality.

I'll try to explain the basics of them. Please keep in mind that this is not exhaustive and a lot of other concepts exists. IMHO, if we must keep short on the rules,
those are, to me, the must-have.

### Naming

Variables, functions, and classes **MUST** have a name that represent what they hold or what they do.

Avoid shortened name, always prefer a long name.

Some examples to avoid :
````php
$i = 0;

foreach ($collection as $data) {
   $this->process($data);
   
   $i++;
}
````

Let's do this instead :
````php
$booksOnWhichPriceHasBeenCalculated = 0;

foreach ($books as $book) {
   $this->calculatePriceOnBook($book);
   
   $booksOnWhichPriceHasBeenCalculated++;
}
````

Also naming purpose is not to represent the type of the value.
````php
// never do this
$uuidString = $this->createStringUuid();

// do this instead
$uuid = $this->createUuid();
````

This is also true for classes :

````php
// So this is a "manager" for book I guess ? Yeah but I don't know what it's doing on books ?
class BookManager
````

Creating general naming on classes is the best way to have in 6 months messy classes that do too many things with hundreds or even thousand of code lines.

Always tell the story of the class in the name. It's calculating price on books ? Okay so :
````php
// Far better
class BookPriceCalculator
````

### Typing

Since php 7 & 8 we have all the things we need to correctly type our code.

**ALWAYS** type your code.

**NEVER** use `mixed` (except for generics type but if you do, you should think about `object` instead and generics phpdoc).

You **MUST** type your class properties :
````php
// never do this
private $thing;

// do this instead
private string $thing = '';
````

When you type class properties, you also **MUST** think about initialization.

If I do this :
````php
public string $thing;
````
It means that, at **any moment** of the runtime, my class instance has a **NOT NULL** `string` value inside `$thing`.
````php
$object = new MyObject('thing');
// a string value must be inside $string, if not, we'll have a fatal error.
$string = $object->thing;
````
So it must likely have a constructor that fill that value with a string.

But on some object we don't have a constructor with parameters (eg: entities), how to do ?
Well, you have two choices :
* Having a default value if you can't have a null here
````php
// Declaration & initialization
public string $thing = '';

// ...
// OR in constructor

// Declaration
public string $thing;

public function __construct()
{
    // Initialization
    $this->thing = '';
}
````
* Authorizing the property to be nullable and initialize it with null
````php
public ?string $thing = null;
````

Also function have to follow the same rules.
````php
// don't
public function doSomething($thing, $stuff);

// do
public function doSomething(Thing $thing, Stuff $stuff): string;
````

For array you **SHOULD** add phpdoc to tell us what's inside as this feature lack for this moment in php.
````php
// what's inside ?

public array $myArray = [];

// better

/** @var Stuff[] */ //equivalent to
/** @var array<int, Stuff> */
public array $myArray = [];

// OR to even tell us the type of the key

/** @var array<string, Stuff> */
public array $myArray = [];
````

### Small functions

You **MUST** keep your function the smallest you can. If you have a big public method, split it in little private ones. A function should never go over 30 or 40 lines of code.
If you are over that threshold, try to extract a little chuck of it inside private methods.

Benefits :
* Comments are not needed inside the function, you can simply find good name for your private functions that tell us what this does.
* Better readability.
* Better reusability if you have to create another public methods by using some logic of the other one.

### Visibility

Visibility has to be taken care of. Think about what you expose outside your class and remember those rules :
* Always declare your properties in `private` to not allow external code to change the state of the internals.
* Be care of `setters`. Remember that a `private` property that have getter and setter, is the same than having it `public`. It means full access.
* Only declare `public` methods that are used outside the class, don't overthink on what will be public in the future, think at the present time.
* Only use the keyword `protected` if your class is **ALREADY** inherited and elements used in child classes
* For inherited `protected` or `public` elements of your class, try to add `final` the more you can to avoid too much overriding on crucial things.

From php 8.1 a good way to avoid many useless combinations of `private` + getter/setters is to declare `readonly`, it allows you to declare
public properties that are immutable.

For instance :
````php
// this
public readonly string $thing = '';

// is the same than having
private string $thing = '';
public function getThing(): string;

// because
$object = new MyObject();
//is allowed
$stuff = $object->thing;
//is not allowed because of readonly
$object->thing = 'toto';
````

### Dto over arrays

Arrays are kind of hell because of their flexibility. Php don't gift us the possibility to type their elements or keys (not speaking about phpdoc).
A rule of thumb is to **AVOID** the maximum you can arrays.

Instead, think about objects. Yes, objects are good, you can control the structure of them and the data in it.

A good way to replace arrays is to use a type of object named `DTO` or Data transfer objects.

A DTO is a simple object that only represent a kind of data. Abuse of them.
They can be used for many things :
* As a ValueObject: Representing a complex primitive value (eg: price, coordinates...). They can be used inside entities,
  to do this, you can create custom doctrine types to represent their database value.
* As a ViewModel: Representing data that have to be consumed by a view
* As a Payload: Representing request or response data
* As a Query: Representing data to build a database query
* ...

````php
// this is bad
$productPrice = [
    'price' => 15.9,
    'currency' => 'EUR'
];
$product->setPrice($productPrice);

// this is really good
$productPrice = new Price(15.9, 'EUR');
$product->setPrice($productPrice);

class Price
{
    public function __construct(private float $amount, private string $currency);
    public function getAmount(): float;
    public  function getCurrency(): string;

    // OR even better from php 8.1
    public function __construct(public readonly float $amount, public readonly string $currency);
}
````

Two rules to follow to create DTO :
* They **must** be immutable, so never create setters on them. If one of the value change, it is a new instance.
* They **must** be simple, don't create overcomplicated construct (use factory class instead), don't obfuscate data with complex getters.

### DRY - Do not repeat yourself (too much)

If you have code that is similar at multiple places, you **MUST** refactor it to avoid that duplication.
You have many options here, it will depend on the context :
* Create a service class for one purpose that will be used as a dependency
* Create traits
* Create an abstract class and inherit it

Always keep in mind that inheritance is maybe the hardest thing in OOP, so try your best to do services and traits instead.

### Domain exception

Never throw native php exception, you **MUST** create a proper exception that is contextual. Also try to give the most detail you
can in message.

For instance :
````php
// this is bad
if (null === $product->getPrice()) {
    throw new \Exception('No Price on the product');
}

// this is good
if (null === $product->getPrice()) {
    throw new ProductPriceExcpetion(sprintf('No price on the product %s', $product->getName()));
}
````

When you have to deal with native exception and third parties one, a good practice is to catch them and throw a proper exception according to the domain.
````php
// this is not so good
/** @throws ProductRepositoryException */
$this->entityManager->persist($product);

// this is good
try {
    $this->entityManager->persist($product);
} catch (SomeOrmException $exception) {
    throw new ProductRepositoryException($exception->getMessage(), $exception->getCode(), $exception)
}
````
In that case you **SHOULD** note that the previous exception is passed to new one, it permits to have the full backtrace.
Also, you can totally create your own message instead of using the previous one.

You try catch **SHOULD** contain only one line.

### Composing over inheritance

As I said before, inheritance is maybe the hardest thing in OOP. It can lead to confuse, complex and difficult to maintain code.
In most cases, inheritance is just considered as a bad practice.

IMHO, it's only useful when you're creating generics components.

Generally, keep in mind that inheritance **MUST** be avoided at all costs in those cases :
* Entities. Never, ever, do this. Don't fall in the **discriminator map rabbit hole**. Use traits and interfaces instead.
* Classes that aren't abstracts.

In most of the cases, inheritance can't be avoided by **composing** classes between them.

For instance :
````php
// this is not so good because our two classes are now coupled, so if SMTPMailer change, it will need to refactor a lot GmailMailer.
class GmailMailer extends SmtpWrapper
{
    public function sendMail(string $mail): void;
} 

// this is good by using composition and interfaces
class GmailMailer implement MailerInterface
{
    public function __construct(private SmtpWrapper $smtpWrapper) {}
    public function sendMail(string $mail): void
    {
        //prepare stuff specifics to google
        
        $this->smtpWrapper->sendMail($mail);
    }
} 
````

It's also completely doable for Repositories. By composing your repository with the entity manager from doctrine, you're less coupled to it
because you'll be forced to implement public method that your application only need.
If you have to change Doctrine to another ORM, even inside the application for a specific repository, it's more possible.

### The S of SOLID - Single responsibility

Single responsibility only mean that a class or a method **MUST** only do one thing. You can achieve that by doing:
* Good naming on the method/class
* Ensure that your methods are smalls

For the classes, it is the same principle.

Let's take an example of one massive thing named `ProductManager` with this API :
````php
class ProductManager
{
    public function createProductFromOrder(Order $order): Product;
    
    public function updateProductInDatabase(Product $product): void;
    
    public function calculateProductPrice(Product $product): void;
}
````
It's easy to see that our class is doing three, completely different things.
* Build a product from another entity or DTO. This is the role of a Builder or a Factory.
* Update the product in database. A repository can do that.
* Calculate the product price. A service class can be responsible for that.

Now that we have identified the responsibilities, let's refactor :
````php
class ProductBuilder
{
    public function fromOrder(Order $order): Product;
}

class ProductRepository
{
    // ... others methods on the repository
    
    public function updateProduct(Product $product): void;
}

class ProductPriceCalculator
{
    public function calculatePrice(Product $product): Price;
}
````

Benefits :
* More classes, but less code in them, so easier to test and to maintain.
* Name that indicate strongly the goal.
* Less coupling.

### The D of SOLID - Dependency invertion

> Warning here : Do not confuse dependency **invertion** with [dependency **injection**](symfony.md#dependency-injection). They have the same acronym and they both involves the `__constructor` of classes, but they are not the same.
> You can do both of them.

This principle is really cool to use once you get it. Dependency invertion goal is to,
reverse dependencies between classes, by using **interfaces** as intermediaries.

Let's take an example.
````php
class FileFetcher
{
    public function __construct(private GoogleCloudStorageBucketLibrary $gcsBucketHandler){}
    
    public function saveDistantFileToLocation(string $fileUrl, string $location): File
    {
        $file = fopen($this->gcsBucketHandler->download($url));
        
        $this->saveFileToLocation($file, $location);
        
        return $file;
    }
}
````
So in that case we have a class that download file from places, specifically google cloud storage. So in our class, we directly use the google library service to use
the bucket. Simple & efficient.

A good part of our application will most likely rely heavily on it. Also, on this example we only have one method, but let's imagine a more complex class with 200 lines
of codes using the `$gcsBucketHandler`.

Our product is in production, everything is fine, cool !

Yeah but, today, we inform you that, by the end of the week, you will not be able to use GCS but use dropbox instead. Ouch.

It will be very difficult, and your critical class that is `FileFetcher` must be heavily reworked, with the risk of introducing new bugs with the rush.

#### Invertion

Now let's take the same example but with this :
````php
class FileFetcher
{
    public function __construct(private FileSystemHandlerInterface $fileSystemHandler){}
    
    public function fetchFile(string $url): File
    {
        $file = fopen($this->fileSystemHandler->download($url));
        
        $this->saveFileToLocation($file, $location);
        
        return $file;
    }
}

class GcsFileSystemHandler implements FileSystemHandlerInterface
{
    public function __construct(private GoogleCloudStorageBucketLibrary $gcsBucketHandler){}
    
    public function download(string $url): File;
}
````

This is it, now the dependency is reversed. You don't need to modify your critical `FileFetcher` class anymore, but instead
implement a new type of `FileSystemHandlerInterface` that will be called, in this case `DropboxFileSystemHandler`.

In conclusion, to reverse a dependency :
* Create an interface with the public method you will use.
* Replace the library dependency usage by the interface.
* Implements the interface on a new class, to use the library as an internal.
* Profit.

### KISS - Keep it stupid simple

Do not overengineer things in the code, if it's not used more than twice do not create useless abstraction, always keep it simple.

Think about the next developers that will maintain and evolve the application after you, everything should be crystal clear even if the documentation disappear.

If you think “yeah, but it will be cool to have this for future development”, is that you are doing it wrong. Focus only on the present by follow clean code conventions.
Do the refactoring only when it will be needed.
