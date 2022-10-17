# Development guidelines

## Testing best practices

### Always test your code

No need to be clearer than that. Tests are vitals. They ensure your code is working at present and future time. You have to spend time on testing.

Rules of thumb :
* Follow the same directory architecture in `tests/{Unit, Functional}/` than the `src` one.
* If you modify existing code, and it doesn't break the tests, you have to investigate why, it probably means that some tests are missing.
* If you are adding new code, cover it with tests.
* Always do some manual testing of the functionality you're implementing. Tests only represent the current state of the application. If it's full broken, but your tests goes green,
  it means that some tests are missing to cover the broken functionality(ies) and you have to write them.
* Add tests that cover the bug you just successfully fixed, it'll ensure that it will never come back again and also will up the coverage.
* Be care of the performance of the test suit, the purpose of testing is not to waste time, but actually to gain time. Invest efforts in the testing solution.
* If you struggle testing something, look at the other tests on the project, they can give you clues. If it's not sufficient and you're still struggling, then you'll probably need to refactor your code or invest more time
  in this test. If the coverage is enought maybe skip it and do it as a bonus later. Again the aim here is not to waste time, but gain time.

### Unit or functional ?

One common question is, what is the difference between a unit test, and a functional test.

Unit test:
* A fully isolated test, that will give the same answer in any order or any context.
* They test the **PUBLIC** methods of a class, one by one, covering each conditions branches and exits, by controlling the output or the calls on internals.
* Fully isolated: All dependencies are mocked.
* Really fully isolated: The input parameters, if objects, are also mocked.

Functional test:
* Test an entire functionality represented by a **PUBLIC** method of a class or by a public endpoint (when testing HTTP APIs or views).
* Dependencies aren't mocked.
* Inputs aren't mocked, either.
* They try to simulate, at their best, a production run, so more tooling is needed than unit testing.
* By definition they are not as isolated as unit tests, but they also **MUST** give the same answer in any order or any context.

On a Symfony/PHPUnit environment, if a test is using the service container to instantiate a service, it's a functional test, because it can't be isolated that way.

Given that :
* Do not call the service container on unit tests, you'll break isolation rules. Use mock instead.
* If you can't create a mock for a particular dependency, well, try to do a functional test on this or refactor your production code.
* Separate Unit and Functional tests, because of the time a functional test will take to run, sometimes you'll need to have some separate process for them.

### Mock usage

Mock are fake objects that emulate an instance of an object. They are perfect to isolate your test and still having the full control of the workflow.

I'll not cover them here, so you should look at the PHPUnit documentation on Mock, Double and Stub to have all the knowledge needed.

Remember that:
* You can't mock final classes.
* You can't mock static or private functions (and you don't have to).
* You can control and check anything on mocks : return values, exception throwing, the number of time a method is called, the parameters given to a method, and also map arguments to return values (ReturnMap).
* **NEVER** partially mock the class you are currently testing.

### Implements an interface for testing

Using the [dependency inversion principle](oop_clean_code.md#the-d-of-solid---dependency-inversion) you can, in Symfony, change the implementations in functional tests.

This is particularly useful when dealing with internet or external communication related data (eg: other HTTP API, external service).

A good example in this project is `GcsBucketHandlerStub`. It's a fake implementation of an interface that is loaded only on test environment with the `services_test.yaml`.
It avoids calling the Google service and to depends on the files that are inside the bucket.

Furthermore, it is a good practice when you want to test functionality that depends on external things, because your tests will be able to run without internet, in a fully isolated way.

But remember that, by doing that, you are mocking something that you may want to test (if GCS is broken, your app will not work). Others solutions exist for that, like having local containers.
Still, both ways can work together.

### Check your coverage & mutation score

Every time you have finished your development and your code is available for review, you **MUST** run test-coverage instruction and look if the code you added is fully (or the most of it) covered.

It is the same for mutation, it ensures that the tests you have written are good enough.

#### Coverage

````bash
make test-coverage
````
Then go in `./coverage` and look at the results via your internet browser.

#### Mutation

````bash
make test-infection
````

Then look at the results directly in the terminal output.
