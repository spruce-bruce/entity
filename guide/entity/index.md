# Entity

The Entity module is an extension of the Kohana ORM module which adds a paradigm for separating the idea of an ORM object that can be used to retrieve database records and perform batch actions on a group of records and an Entity object that represents a single record. I discuss advantages and disadvantages of this model on the [Motivation](motivation) page.

## What?
When using Kohana's ORM module you can make a call like `ORM::factory('User')->find_all()` which will perform a query that returns all of your users. The `find_all` call returns a `Database_Result` which in turn contains a result set of User ORM objects. When using the Entity module the `find_all` call returns an array of Entity objects. Entity objects are returned *if and only if* you have defined an entity object that partners with an ORM class. In our previous `find_all` example, if there is no User entity defined then it will return a normal Database_Result object with ORM objects.

The goal of all of this is that we now have a class that defines an individual User object that doesn't have all of the baggage that comes with ORM objects. An entity object can not be used to retrieve other entity objects. It exists solely to represent one "entity", in this case as user. You should include methods in your Entity classes that perform operations on one record and you should put methods in your ORM class that perform operations on many records. If you have questions about why then check out the [Motivation](motivation) page.

## Installation
This module follows normal module installation rules (i.e., add module to MODULES path, include in bootstrap) with one caveat. Because the Entity module extends classes from the ORM module it must be included in the bootstrap first. For this reason I generally include the entity module first in my modules array in applications' bootstrap.php file.

An example modules array might look something like this:

~~~
Kohana::modules(array(
    'entity'     => MODPATH."entity",
    'auth'       => MODPATH.'auth',       // Basic authentication
    'database'   => MODPATH.'database',   // Database access
    'orm'        => MODPATH.'orm',        // Object Relationship Mapping
    'unittest'   => MODPATH.'unittest',   // Unit testing
    'userguide'  => MODPATH.'userguide',  // User guide and API documentation
    ));
~~~