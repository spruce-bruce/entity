# Entities and Relationships
You can interact with an Entity's relations the same way you would interact with an ORM object's relations. You will still define relationships in the ORM class, and then you'll be able to access the related columns by referencing the relationship on the Entity object.

In this example we're going to set up a basic user entity, and in our applications our users can make posts. So a user will have a has many relationship to posts.

## Step 1 : set up your files

Create a User model at `APPPATH/classes/Model/User.php`

~~~
class Model_User extends ORM{

}
~~~

Now create a another file called User.php `APPPATH/classes/Entity` directory. Save the following into the file:

~~~
class Entity_User extends Entity{
    
}
~~~

## Step 2 : set up the post relationship
This step modifies the User model from the last step by adding a has many relationship for posts.

~~~
class Model_User extends ORM{
    protected $_has_many = array(
        'posts' => array(),
    );
}
~~~

## Step 3 : access your relationship
Now you can access the user's posts through the User Entity class. If you have an entity set up for posts then $user->posts will return an array of entity objects. If you only have an ORM class set up for posts then you'll get a Database_Result object containing ORM objects.

~~~
$user = new Entity_User(1); //get a user entity object

foreach($user->posts as $post){
    echo $post->date; //echoes the date for a post
}
~~~