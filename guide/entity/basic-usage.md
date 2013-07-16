# Basic Usage

In order to start using the Entity object you must create an Entity class. I will use an imaginary users table and ORM class to illustrate the basics of the Entity module.

## Set Up Your First Entity

### Step 1 : Create the Entity Directory

By default ORM objects will look in the `APPPATH/classes/Entity` directory for your Entity classes. If you prefer to store you Entity classes elsewhere please check out the [Non-Standard Entity Locations](examples/non-standard-entity-locations) examples.

### Step 2 : Create your ORM and Entity Classes

Create a file called User.php in the `APPPATH/classes/Model` directory. Save the following into the file:

~~~
class Model_User extends ORM{

}
~~~

Now create a another file called User.php `APPPATH/classes/Entity` directory. Save the following into the file:

~~~
class Entity_User extends Entity{
    
}
~~~

Your set up is done! Now when you peform `find()` and `find_all()` calls will return Entity_User objects. If you prefer having User objects instead of Entity_User objects you can move on to the optional step 3.

### Step 3 : Create a User class (optional)
If you prefer the syntax of using a `User` class instead of an `Entity_User` class you can simply create a new file called User.php in your `APPPATH/classes` directory which looks like this:

~~~
class User extends Entity_User{
    
}
~~~

You can also follow the instructions on the [Non-Standard Entity Locations](examples/non-standard-entity-locations) page for how to create a `User` class without extending an `Entity_User` class.

## Getting an Entity Object
This example assumes that you've completed Step 3 above. I will be referencing the `User` class rather than the `Entity_User` class.

### Get an entity when you know its id
~~~
$user = new User(1);

echo (string) $user->id; // Outputs 1
echo $user->first_name;  // Outputs "Aaron"
echo $user->last_name;   // Outputs "Bruce"
~~~

-OR-

~~~
$user = ORM::factory("User")->where('id', '=', '1')->find();
~~~

-OR-
~~~
$user = ORM::factory("User", 1); // I'm on the fence about whether or not this should 
                                 // return an ORM object or an Entity object.
                                 // Send me feedback on github if you have an opinion.
~~~

### Get an array of Entities
Use your ORM objects to perform queries in the same way you always have. If you have set up an Entity class you will receive Entity objects as the result.
~~~
$users = ORM::factory("User")->find_all();

foreach($users as $user){
    echo "User's name is " . $user->first_name . " " . $user->last_name . ".";
}
~~~

## Create a New Record Using an Entity Object
Most of the ORM methods that allow you to perform operations on single records are exposed in the Entity api. The `save()` method is one such example. You can call save on an Entity object and it will be passed to an ORM object and behave as you'd expect.

~~~
$user = new User();
$user->first_name = "Joe";
$user->last_name = "Blow";
$user->save();
~~~

## Get an Entitie's ORM Object
Every entity object stores a reference to the ORM object that is backing it up. If you ever need the ORM object from an Entity simply call the `orm()` method.
~~~
$user = new User(1);
$user_model = $user->orm();
echo get_class($user_model); // Outputs "Model_User"
~~~