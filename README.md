# DependencyPocket

[![Build Status](https://travis-ci.org/mmghv/dependency-pocket.svg?branch=master)](https://travis-ci.org/mmghv/dependency-pocket)
[![Latest Stable Version](https://poser.pugx.org/mmghv/dependency-pocket/v/stable)](https://packagist.org/packages/mmghv/dependency-pocket)
[![Total Downloads](https://poser.pugx.org/mmghv/dependency-pocket/downloads)](https://packagist.org/packages/mmghv/dependency-pocket)
[![Latest Unstable Version](https://poser.pugx.org/mmghv/dependency-pocket/v/unstable)](https://packagist.org/packages/mmghv/dependency-pocket)
[![License](https://poser.pugx.org/mmghv/dependency-pocket/license)](https://packagist.org/packages/mmghv/dependency-pocket)

A new form of "Dependency Injection" in PHP


Not to be confused with [Dependency Injection Container](http://martinfowler.com/articles/injection.html) or `Ioc Container` like [Symfony DI Container](http://symfony.com/doc/current/components/dependency_injection.html), [Pimple](http://pimple.sensiolabs.org/) or [Laravel Service Container](https://laravel.com/docs/5.3/container),

**DependencyPocket** is not a `DI Container` in that way, It's rather a new form of `Dependency Injection`.
Essentially, there're two ways of dependency injection : `Constructor Injection` and `Setter Injection`,
I'd like to think of this technique as the third type, the `Pocket Injection`.

## Installation

#### Using composer
```
composer require mmghv/dependency-pocket "~0.2"
```

## usage
We use this pocket inside our classes to hold the dependencies, we first create a new pocket :

```PHP
use mmghv\DependencyPocket;

// ...

$this->pocket = new DependencyPocket();
```

Then we add the dependencies, We first define them (defining the `Name` and the `Type` of each dependency) :

```PHP
$this->pocket->define([
    'dep1',  // allow any type
    'dep2' => 'string',  // primitive type
    'dep3' => 'array',  // primitive type
    'dep4' => 'App\Model\Article'  // class or interface
]);
```

Then we set our dependencies (set dependencies values) :

```PHP
$this->pocket->set([
    'dep1' => true,
    'dep2' => 'some value',
    'dep3' => [1, 2],
    'dep4' => $myArticle
]);
```

Then when we want to get a dependency we simply do :

```PHP
$dep = $this->pocket->get('myDep');
// or
$dep = $this->pocket->myDep;
```

Or we can use `Property Overloading` to easily access our dependencies from our class (or subclasses) :

```PHP
public function __get($name)
{
    if ($this->pocket->has($name)) {
        return $this->pocket->get($name);
    } else {
        throw new \Exception("Undefined property: [$name]");
    }
}
```

## When it's useful and the technique to use it
Basically Its useful when your class has many dependencies which not all of them are required so you don't want your constructor to have all these dependencies but still need a way to easily change them in the subclasses and tests.

Imagine you have a class with 5 dependencies but only 2 of them are essential and the other 3 can  be set to some default values, Then you extend this class and the subclass can resolve one of the two essential dependencies to a default value and needs to replace one of the optional dependencies of the parent class, You need to be able to do that and want your final class to have only one dependency in the constructor but still be able to change any default ones from any future subclasses as well as the ability to mock any of these dependencies in the tests.

Using **DependencyPocket** you can achieve that like the following without using a setter for each dependency and also mocking dependencies for tests is easier than using `Setter Injection` method :

```PHP
// Manager.php

namespace App\Managers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use mmghv\DependencyPocket;

class Manager
{
    protected $pocket;

    /**
     * Define class dependencies.
     */
    protected function defineDependencies()
    {
        if ($this->pocket) {
            return true;
        }

        $this->pocket = new DependencyPocket();

        $this->pocket->define([
            'app'           => 'Laravel\Lumen\Application',
            'model'         => 'Illuminate\Database\Eloquent\Model',
            'validator'     => 'Illuminate\Contracts\Validation\Factory',
            'request'       => 'Illuminate\Http\Request',
            'redirectUrl'   => 'string',
        ]);
    }

    /**
     * Create new manager.
     *
     * @param  Application $app
     * @param  Model       $model
     * @param  array       $dependencyPocket
     */
    public function __construct(Application $app, Model $model, array $dependencyPocket = [])
    {
        $this->defineDependencies();

        $this->pocket->set($dependencyPocket += [
            'app'           => $app,
            'model'         => $model,
            'validator'     => $app->make('validator'),  // default value
            'request'       => $app->make('request'),  // default value
            'redirectUrl'   => $app->make('session')->previousUrl(),  // default value
        ]);
    }
}
```

```PHP
// ArticleManager.php

namespace App\Managers;

use Illuminate\Contracts\Foundation\Application;
use App\Models\Article;

class ArticleManager extends Manager
{

    /**
     * Define any additional class dependencies, Declare this function
     * only when you need to define new dependencies.
     */
    protected function defineDependencies()
    {
        if (parent::defineDependencies()) {
            return true;
        }

        $this->pocket->define([
            // define any new dependencies for this class
        ]);
    }

    /**
     * Create new article-manager.
     *
     * @param  Application $app
     * @param  array       $dependencyPocket
     */
    public function __construct(Application $app, array $dependencyPocket = [])
    {
        // always call this first
        $this->defineDependencies();

        // default value for $model dependency
        $model = new Article();

        // call parent construct and pass dependencies
        parent::__construct($app, $model, $dependencyPocket += [
            'validator'     => new CustomValidator(), // replace default dependency value
            // add any new dependencies for this calss, needs to be defined first in 'defineDependencies()'
        ]);
    }
}
```

Then when we instantiate the `ArticleManager` class we only need to pass one dependency like this :

``` PHP
$manager = new ArticleManager($app);
```

But also we have the ability to easily replace any default dependencies when we want like this :

```PHP
$manager = new ArticleManager($app, [
    'model' => $anotherModel,
    'validator' => $anotherValidator
]);
```


## Contributing
This is a relatively new technique so any contributions (suggestions, enhancements to the technique used) are welcome. PSR-2 standards are used and tests should cover any changes in case of PRs.

## License & Copyright

Copyright © 2016, [Mohamed Gharib](https://github.com/mmghv).
Released under the [MIT license](LICENSE).
