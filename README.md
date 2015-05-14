# InjectionFactory

A PHP factory that support dependency injection

## Installation

_Not yet available on Composer/Packagist_

## Usage

```php
class A {
    /**
     * @param B $b The class B
     */
    public function __construct(B $b) {
        // ... Do something with $b
    }
}

class B {}

$a = MacFJA\InjectionFactory\Factory::create('A')
```

```php
class A {
    protected $b;
    protected $myVar;

    public function __construct(B $b, $myVar = 'hello') {
        $this->b = $b;
        $this->myVar = $myVar;
        // ... Do other stuff
    }

    public function info() {
        return $this->b->info().' - '.$this->myVar;
    }
}

class B {
    public function info() {
        return 'I am B';
    }
}

$a = MacFJA\InjectionFactory\Factory::create('A');
echo $a->info(); // Output "I am B - hello"

$a2 = MacFJA\InjectionFactory\Factory::create('A', array('myVar' => 'world'));
echo $a->info(); // Output "I am B - world"
```

```php
class A {
    protected $b;
    protected $myVar;

    public function __construct(B $b, $myVar = 'hello') {
        $this->b = $b;
        $this->myVar = $myVar;
        // ... Do other stuff
    }

    public function info() {
        return $this->b->info().' - '.$this->myVar;
    }
}

class B {
    public function info() {
        return 'I am B';
    }
}

$a = MacFJA\InjectionFactory\Factory::get('A', array('myVar' => 'hello world'));
echo $a->info(); // Output "I am B - hello world"

$a2 = MacFJA\InjectionFactory\Factory::get('A', array('myVar' => 'foo'));
echo $a->info(); // Output "I am B - hello world"
```

## Overview

The mains methods are:

 - `Factory::create($class, $arguments=array())` which create a new instance of the class `$class`
 - `Factory::get($class, $arguments=array())` which return a previously a singleton of the class `$class`

### `Factory::create` method

This method create a new object of type `class`.
The first parameter of the method is the class identifier (see **`$class` parameter of methods `Factory::create` and `Factory::get`** chapter),
the second is an optional list of parameters that the function will need (see **`$arguments` parameter of methods `Factory::create` and `Factory::get`** chapter).

### `Factory::get` method

Return a singleton that will be return every times you call `Factory::get` with the same class name.
If the singleton does not exists, a new instance will be created with `$arguments` (see **`Factory::create` method** chapter)

### `$arguments` parameter of methods `Factory::create` and `Factory::get`

The `$arguments` parameter is an array of value that will be used for creating a instance of the class.
The array can be associative or indexed.

The prefer form is the associative one that authorize to provide parameters in any order and don't constraint to provide all require parameters.

Here the schema of resolution:

For **associative** array

```
Loop on [parameters of method/constructor (item: $param)]
    If [the name of $param] is a [key of $arguments] Then
        Use [value of $arguments with the key [the name of $param]]
    Else If [the class/type $param] is defined Then
          Use [get (or create) a instance of [the class/type $param] with [$arguments] as arguments]
    Else If [$param] is optional Then
          Use [default value of $param]
    Else
          Throw an error
    End If
End Loop
```

For **indexed** array

```
Loop on [parameters of method/constructor (item: $param)]
    If [the position of $param] is lower or equals to [the number of item in $arguments] Then
        Use [value of item number [the position of $param] of $arguments]
    Else If [the class/type $param] is defined Then
          Use [get (or create) a instance of [the class/type $param] with [$arguments] as arguments]
    Else If [$param] is optional Then
          Use [default value of $param]
    Else
          Throw an error
    End If
End Loop
```

### `$class` parameter of methods `Factory::create` and `Factory::get`

The `$class` parameter can be in 4 forms:

 - an array (PHP callback array) where the first item is the classname (FQCN), and the second a method name
 - a FQCN (for _Fully Qualified Class Name_): `[Namespace] + "\" + [class name]`
 - a FQSEN (for _Fully Qualified Structural Element Name_): composed by: `[FQCN] + "::" + [method name]` (like a static method call)
 - a derivated FQSEN: composed by: `[FQCN] + "->" + [method name]` (like a object method call)

If `$class` is a FQCN (or the array with only one argument), then the factory will call the class constructor to create a instance.
If `$class` have a method (the 2 FQSEN form + the array form with 2 arguments) then the factory will call this method.

The second case have 2 behavior:
 - If the method is **static** then a static call will be made
 - Else, an instance of the class will be created, and then the method will be call (on the instance)

Here the schema of resolution:

```
                               (3) | Callback exist -> Use the callback
                                   |
     (1) | Only class information -|
         |                         |
         |                     (4) | No callback -> Call the constructor of the class
         |
 $class -|
         |
         |
         |             (5) | Method is no static -> create a instance with (1) -> call of the function
         |                 |
     (2) | Class + method -|
                           |
                       (6) | Method is static -> Make a static call of the function
```

## Limitation

The factory is build for been used with zero configurations, but rely a lot on constructor and method definition.
To inject classes, the class need to **type** (static typing) every argument that must be injected.

The following class won't work:

```php
class A {
    /**
     * @param B $b The class B
     */
    public function __construct($b) {
        // ... Do something with $b
    }
}

class B {}
```

But this definition will work:

```php
class A {
    /**
     * @param B $b The class B
     */
    public function __construct(B $b) {
        // ... Do something with $b
    }
}

class B {}
```

The only change is that the parameter `$b` is typed in the second case.

As you can see, the Factory don't read the DocComment to guess the Type of the parameter

## Similar project

 - https://github.com/watoki/factory