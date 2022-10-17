# Development guidelines

## Doctrine best practices

### Migrations

When modifying entities or creating/deleting one, you **MUST** check if your database is up-to-date with the schema.

Executing these commands will tell you if you're up to the schema and if you need to create a new migration.
````shell
make migrate
make db-validate-schema
````

You **MUST** execute schema validation before creating your PR.

If you have to create one, well, simply execute this command.
````shell
make migration
````

It will automatically create the migration in `./migrations` folder.

Rules when creating migrations :
* Never, ever, modify an existing migration that have not been created on your branch. Even if it's about the change of only one property of an existing entity.
  You'll break production and force others developers to erase their local database.
* Always set up a name on it, to know at execution what it's doing, what is its purpose.
* Remove the automatically added line `CREATE SCHEMA public` on the `down()` function.
* Remove comments.
* Test it and test the rollback.

### When to flush

Only one rule when committing changes to the database : you **MUST** never call `flush()` method inside a loop. It will break performances for nothing.
The only exception is when you want batch commit, but even here you control the `flush()` call with an internal count.

For instance :
````php
// bad, bad, bad. If you have more than 10 entities, this code will take minutes to execute.
foreach ($entitiesToCommit as $entity) {
    $this->entityManager->persist($entity);
    $this->entityManager->flush();
}

// always do this instead
foreach ($entitiesToCommit as $entity) {
    $this->entityManager->persist($entity);
}

$this->entityManager->flush();
````

And if you want batch commit, you're authorized to call the flush inside the loop, with only one condition: You have to control the number of entities persisted and be sure it's high enought.

Always test the number with bench (easy with test and fixtures) and try to found the best one possible.
````php
$batchSize = 1000; //1000 is a good starting point.

$persistedEntityCount = 0;
foreach ($entitiesToSave as $entity) {
    $this->entityManager->persist($entity);
    $persistedEntityCount++;
    
    if (0 !== ($persistedEntityCount % $batchSize)) {
        continue;
    }
    
    $this->entityManager->flush();
}

// call the flush again to finally commit entities that are in the last batch
$this->entityManager->flush();
````

### Entities relationship

When you have to do add a relationship between two entities :
* Identify which relationship is involved by looking at Doctrine official documentation.
* Set up the relationship, not with the entity foreign id directly, but with the entity itself.
* Create a new migration.

For example, Book need Author relationship. A Book has one Author, An Author can have multiple Book(s). It's a OneToMany relationship.
The Many part is always holding this kind of relations, so the main work has to be done on Book.
````php
// On the book side.
class Book
{
    #[ORM\ManyToOne(targetEntity: Author::class, inversedBy: 'books')]
    private ?Author $author = null; // remember Typing section
    
    // getter/setter
}
````

Few comments on this :
* Doctrine will know it is a foreign key and add `author_id` on Book's table.
* `inversedBy` section is only needed if you have a bidirectional relationship. That simply means that Author entity needs to know, at runtime, the Book it holds on the database,
  so Author has also a property related to Book entity.
  If you don't need/have it, remove `inversedBy` option in the attribute.
````php
// On the author side, only if you need bidirectional relationship.
class Author
{
    #[ORM\OneToMany(targetEntity: Book::class, mappedBy: 'author')]
    private Collection $books;
    
    public function __construct()
    {
        // initializing your collections as empty on instantiation, is a good practice.
        $this->books = new ArrayCollection();
    }
    
    // getter/setter
}
````
Note that on the One relationship part, if bidirectional, we use here the `mappedBy` option, to tell Doctrine, Book entity is holding the relationship.

### Entities setters

A good practise is to use setters that return self object (Fluent setters). So that we can chain setters.

````php
public function setName(string $name): self
{
    $this->name = $name;
    return $this;
}
````

If you are using PhpStorm there is a checkbox during [generation](https://www.jetbrains.com/help/phpstorm/generating-code.html) of Setters and Getters, that will do the job for you.

![Option](https://i.stack.imgur.com/NkIxC.png)

### Iterate on large dataset

On many cases, to fetch and iterate on all data of a given table, we simply call `findAll` and do a basic foreach on it.
Great with 5 rows, but with more it's not at all performant. Because here, Doctrine will make a big select, hydrate all the results into entities and hold them in memory.

If you have a large dataset, you probably want to use the internal cursors of databases. To do that with Doctrine, create your own query and call `toIterable()` method.
````php
public function iterateOnAllByImport(Import $import): iterable
{
    return $this->entityManager->createQueryBuilder()
        ->select('entity')
        ->from($this->entityName, 'entity')
        ->where('entity.import = :import')
        ->setParameter('import', $import)
        ->getQuery()
        ->toIterable();
}

foreach ($this->repo->iterateOnAllByImport($import) as $entity) {
    //...
}
````
Here, on each row, it is the database that holds the result set, doctrine only hydrate row by row. It is far more efficient and performant.

As it needs more code, it is only recommanded on queries that return more than 10 rows.

### Caching query results

If you have to execute some query inside a large loop (for example: you need to get one entity based on some data on the current item of the loop, to make a relation), you **SHOULD** implement some cache
to avoid calling the database each time.

Multiple caching solution exists :
* Application memory: store the result in an array or a collection, php will hold it in the application memory.
* In memory external service: store the result in a solution like Redis.
* Files: Symfony cache or NoSQL solution.

````php
// no cache
foreach ($entitiesToCommit as $entity) {
    $otherEntity = $this->someRepo->findOneByRef($entity->getRef()); // If you have 1000 entities inside $entitiesToCommit, you will make 1000 queries.
    
    $entity->setOtherEntity($otherEntity);
    
    //...
}
//...

// with cache
foreach ($entitiesToCommit as $entity) {
    if (null === $otherEntity = $this->cache->findOtherEntityByKey($entity->getRef())) {
        // the query is only executed if we don't find the otherEntity given its cache key, that is, in this case, $entity->ref.
        $otherEntity = $this->someRepo->findOneByRef($entity->getRef());
        
        $this->cache->save($entity->getRef(), $otherEntity);
    }
    
    $entity->setOtherEntity($otherEntity);
    
    //...
}
//...
````

### Creating fixtures

After the addition of a new entity, you **SHOULD** create a fixture for this. It will permit to have directly test data :
* Be sure the database is correctly setted up.
* Ensure the entity configuration is working.
* Having food for your repositories in functional tests.

Simply create your new fixtures in `./fixtures/{env}` folder (env = dev|test) and follow this convention :
* Camel case `{module name}_{entity name}.yaml`
* Inside the file, declare your fixtures as it
````yaml
GtfsService\Gtfs\Entity\GtfsAgency:
    # Your fixture name, it's a row in the database, represented by the name agency_test_1
    agency_test_1:
        agencyId: 'agency_test_1'
        import: '@import_terminuscity_1' #relation to Import entity fixture named @import_terminuscity_1
        network: '@network_terminuscity' #relation to Network entity fixture named @network_terminuscity
````
The tool used here is Nelmio/Alice, and it is very powerful. For more information on it, you'll find everything on the official documentation.
