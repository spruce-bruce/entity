# ORM api
You will be able to use an Entity object in many of the same ways as an ORM object. The Entity object *is not* a subclass of ORM, but it does have a reference to an ORM object and some calls are passed directly to that ORM object. Methods that are passed to the ORM object are explicitly defined in the `Kohana/Entity.php` class. The task of selecting methods to pass on is not complete, and a full review of allowable ORM methods is needed. The following are the ORM methods that can be called directly on a Entity object.

## Relationship Methods
- add()
- has()
- remove()

## Upkeep Methods
- pk()
- loaded()

## CRUD Methods
- save()
- __set()
- __get()