<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Base class for "Entity" objects.
 * 
 * Entity objects are objects that represent a single row from a mysql table. If you
 * have a users table then an Entity_User object will represent a single user.
 * These entity classes are a way to get an individual user object while at the
 * same time still maintaining some manner of relationship with the table in which
 * the object's data is stored.
 * 
 * In order to use the Entity class extend it. I usually put them in 
 * /application/classes/entity but it's up to you. 
 * 
 * The Entity class works in conjunction with the "Savable" interface.
 * 
 * @todo create guide page for entities
 */
abstract class Kohana_Entity{
    const BELONGS_TO = 10;
    const HAS_ONE = 20;
    const HAS_MANY = 30;

    /**
     * reference to the ORM (active record) object for the entity
     * @var ORM
     */
    protected $_orm = false;

    /**
     * Stores data from related tables
     * @var array
     */
    protected $_relationships = array();

    /**
     * construct the entity object
     *
     * $arg will either be an array of values, an (int) id or an orm object
     * 
     * @param mixed $arg - params for building the object
     */
    public function __construct($arg = false){

        //set the orm class name
        $this->_orm = ($this->_orm) ? ($this->_orm) : ("Model_" . get_called_class());

        /* Handle object creation depending on the type of ARG
        //---------------------------------------*/
        if($arg instanceof ORM){
            /* The argument is ORM already. Set it and forget it
            //---------------------------------------*/
            $this->_orm = $arg;

        } else if($arg) {
            /* Argument is set, we assume that it's an ID of some sort (string or int)
            //---------------------------------------*/
            if($this->_orm && $arg) { 
                $this->_orm = new $this->_orm($arg);
            }
        } else {
            /* Argument is default, create a blank ORM
            //---------------------------------------*/
            $this->_orm = new $this->_orm();
        }
    }

    /**
     * Returns an array of all the '_col' members. The keys will be the name
     * of the member with the '_col' chopped off the end.
     *
     * @todo update to work with $this->_orm
     * @return array
     */
    public function as_array(){
        if($this->_orm){
            $data = $this->_orm->as_array();
        }else{
            $data = array();
            foreach($this as $key=>$val){
                if(substr($key, -4) == '_col' && $val){
                    $data[substr($key, 0, -4)] = $val;
                }
            }
        }
        return $data;
    }

    public function orm(){
        return $this->_orm;
    }

    public function __toString(){
        return (string) $this->_orm;
    }
    
    public function trim_columns(){
        
    }
    
    /**
     * __get() magic method to retrieve properties from the underlying ORM object
     * 
     * @param  string $key
     * @return mixed
     */
    public function __get($key){
        $val = null;
        if($this->_orm){
            //check if the property is an ORM object. If it is
            //then it represents a *:many set of objects
            if($this->_orm->{$key} instanceof ORM){

                if(isset($this->_relationships[$key])){
                    $val = $this->_relationships[$key];
                }else{

                    switch($this->relationship($key)){
                        case self::HAS_ONE:
                        case self::BELONGS_TO:
                            $val = $this->_orm->{$key}->entity();
                            /* If false after this point, we don't have an entity. Return ORM obj instead
                            //---------------------------------------*/
                            if(!$val) {
                                $val = $this->_orm->{$key};
                            }
                            break;
                        case self::HAS_MANY:
                            $val = $this->_orm->{$key}->find_all();
                            break;
                        default:
                            $val = $this->_orm->{$key};
                            break;
                    }
                    
                    $this->_relationships[$key] = $val;
                }
            }else{
                $val = $this->_orm->{$key};
            }
        }else{
            throw new Missing_ORM_Exception();
        }
        return $val;
    }

    /**
     * __set() magic method to set properties in the underlying ORM object
     * @param string $key 
     * @param mixed $val 
     */
    public function __set($key, $val){
        //set is called before __construct in some cases... wtf?
        if(!($this->_orm instanceof ORM)){
            $this->__construct();
        }
        if($this->_orm instanceof ORM){
            $this->_orm->{$key} = $val;
        }else{
            throw new Missing_ORM_Exception("There is no ORM object linked to this Entity instance");
        }
    }

    /**
     * __call() magic method will help us use ORM methods as our own. Specifically we want to
     * be able to do Entity::has(), Entity::add() and Entity::remove() for managing relationships.
     * save() and delete() may be added here.
     * 
     * @param  string $name      method name
     * @param  array  $arguments array of args passed in original call
     * @return Entity            if successful, return self for chaining
     * @return bool              if unsuccessfu, return false
     */
    public function __call($name, $arguments){
        $success = false;
        $convert_orm_to_entity = false;

        if(!$this->_orm){
            throw new Missing_ORM_Exception("There is no ORM object linked to this Entity instance");
        }

        //validate number of args
        switch($name){
            case "add" :
                if(count($arguments) != 2){
                    throw new Entity_Call_Exception("The $name() method requires 2 arguments.");
                }
                $success = true;
                $convert_orm_to_entity = true;
                break;
            case "has":
            case "remove":
                if(count($arguments) < 1){
                    throw new Entity_Call_Exception("The $name() method requires at least one argument.");
                }

                if($arguments[0] instanceof Entity){
                    $arguments[0] = $arguments[0]->orm();
                }

                $success = true;
                $convert_orm_to_entity = true;
                break;
            case "loaded":
            case "pk": 
            case "save":
            case "delete":
                $success = true;
                break;
            default :
                throw new Entity_Call_Exception("The $name() method does not exist or is not supported.");
        }

        /* Convert Entity Arguments to ORM if needed
        //---------------------------------------*/
        if($convert_orm_to_entity) {
            foreach($arguments as &$argument) {
                if($argument instanceof Entity) {
                    $argument = $argument->orm();
                }
            }
        }

        //if args are correct and we explicitly handle this function then we can pass the
        //call to the orm object
        $res = null;
        if($success){
            $res = call_user_func_array(array($this->_orm, $name), $arguments);
        }

        return ($success) ? ($res) : (false);
    }

    private function relationship($key){
        $relationship = false;

        if(array_key_exists($key, $this->_orm->belongs_to())){
            $relationship = self::BELONGS_TO;
        }else if(array_key_exists($key, $this->_orm->has_one())){
            $relationship = self::HAS_ONE;
        }else if(array_key_exists($key, $this->_orm->has_many())){
            $relationship = self::HAS_MANY;
        }

        return $relationship;
    }



    public function values($post){
        if($this->_orm){
            $this->_orm->values($post);
            return $this; // Chain the Entity, not the ORM object
        }else{
            throw new Missing_ORM_Exception("There is no ORM object linked to this Entity instance");
        }
    }

    /*** Getters ***/
    
    /**
     * Get the label for a given column if it is set
     *
     * @author  tspencer
     * @param   string      $col        Column name to get the field from
     * @return  string                  Label for this column if it is set
     *****************************************************/
    public function get_label($col){
        if(!$this->_orm){
            throw new Missing_ORM_Exception("There is no ORM object linked to this Entity instance");
        }
        $labels = $this->_orm->labels();
        return (isset($labels[$col])) ? $labels[$col] : '';
    }
}

class Entity_Exception extends Kohana_Exception{}
class Entity_Call_Exception extends Entity_Exception{}
class Missing_ORM_Exception extends Entity_Exception{
    protected $message = "There is no ORM object linked to this Entity instance";
}