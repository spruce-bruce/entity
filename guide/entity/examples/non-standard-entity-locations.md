# Non-Standard Entity Locations
By default when an ORM object is checking to see if you've created a corresponding Entity it will look in the `APPPATH/classes/Entity` directy. You can overwrite this behavior by setting the `$_entity` variable in your ORM class.

~~~
class Model_User extends ORM{
    protected $_entity = "Person";
}
~~~

The above example model will now attempt to find the Person class and dutifully return Person objecst when calling find() and find_all().