<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Overwriting ORM to return Entity objects where available from find() and 
 * find_all() methods
 *
 * Finding entities that aren't explicitly set only works in PHP 5 >= 5.3.0
 *
 * @author  abruce
 * @copyright Cardinal Path 2013
 */
class ORM extends Kohana_ORM {
    /* Stores Entity Class (if it exists)
    //---------------------------------------*/
    protected $_entity = false;

    /* Stores definition of relationships as 'key' => 'callback'
    //---------------------------------------*/
    protected $_relations = array();

    /**
     * Creates and returns a new model. If id is provided and an entity
     * class exists for the model then factory() will return an entity
     * object.
     *
     * @chainable
     * @param   string  $model  Model name
     * @param   mixed   $id     Parameter for find()
     * @return  ORM
     * @return  Entity
     */
    public static function factory($model, $id = NULL)
    {
        $res = parent::factory($model, $id);

        if($id && ($entity = $res->entity($res))){
            $res = $entity;
        }
        return $res;
    }

    /**
     * Finds and loads a single database row into the object.
     *
     * @return ORM or Entity
     */
    public function find(){
        $res = parent::find();

        if($this->entity_exists()){
            $res = $this->entity($res);
        }

        return $res;
    }

    /**
     * Finds multiple database rows and returns an array of the rows found.
     *
     * @return Database_Result or array
     */
    public function find_all(){
        $res = parent::find_all();

        if($this->entity_exists()){
            $res = $this->entity($res);
        }

        return $res;
    }

    /**
     * determine if there is an entity for this model
     * @return bool
     */
    private function entity_exists(){
        $entity = false;

        /*
         * if an entity isn't explicitly set we'll try to find one based
         * on the model name. Model_Event means we're looking for the event
         * entity, etc.
         */
        if(!$this->_entity){
            $class = get_called_class();
            $class_parts = explode("_", $class);

            $class_type = array_shift($class_parts); // Expect this to be 'Model'
            $class_path = implode('/', $class_parts);
            $entity_name = $class_parts[count($class_parts) - 1];
            
            if(Kohana::find_file('classes', $class_path)){
                $this->_entity = $entity_name;
                $entity = true;
            }
        }else{
            $entity = true;
        }
        return $entity;
    }

    /**
     * Returns an entity object for the current ORM obj or if
     * an array of ORM objects are supplied entity() will return
     * an array of entity objects, one for each ORM object in the array
     * 
     * @param  array or false $objs
     * @return array or Entity
     */
    public function entity($objs = false){
        $entities = false;
        if($this->entity_exists()){
            if($objs instanceof Database_MySQL_Result){
                $entities = array();
                foreach($objs as $orm_obj){
                    $entities[] = new $this->_entity($orm_obj);
                }
            }else if($objs){
                $entities = new $this->_entity($objs);
            }else{
                $entities = new $this->_entity($this);
            }
        }

        return $entities;
    }

    /**
     * Handles retrieval of all model values, relationships, and metadata.
     *
     * @param   string $column Column name
     * @return  mixed
     */
    public function __get($column)
    {
        if (array_key_exists($column, $this->_object))
        {
            return (in_array($column, $this->_serialize_columns))
                ? $this->_unserialize_value($this->_object[$column])
                : $this->_object[$column];
        }
        elseif (isset($this->_related[$column]))
        {
            // Return related model that has already been fetched
            return $this->_related[$column];
        }
        elseif (isset($this->_relations[$column]))
        {
            // If a callback exists, our plan is simple: Execute and return
            if(isset($this->_relations[$column]['get'])) {
                $callback = $this->_relations[$column]['get'];
                $this->_related[$column] = $this->$callback();
                return $this->_related[$column] ;
            } else {
                throw new ORM_Relation_Exception("Relations callback does not have 'get' method set");
            }
        }
        elseif (isset($this->_belongs_to[$column]))
        {
            $model = $this->_related($column);

            // Use this model's column and foreign model's primary key
            $key = (isset($this->_belongs_to[$column]['far_key'])) ? 
                ($this->_belongs_to[$column]['far_key']) : 
                ($model->_primary_key);

            $col = $model->_object_name.'.'.$key;
            $val = $this->_object[$this->_belongs_to[$column]['foreign_key']];

            $model->where($col, '=', $val)->find();

            return $this->_related[$column] = $model;
        }
        elseif (isset($this->_has_one[$column]))
        {
            $model = $this->_related($column);

            // Use this model's primary key value and foreign model's column
            $col = $model->_object_name.'.'.$this->_has_one[$column]['foreign_key'];
            $val = $this->pk();

            $model->where($col, '=', $val)->find();

            return $this->_related[$column] = $model;
        }
        elseif (isset($this->_has_many[$column]))
        {

            $model = ORM::factory($this->_has_many[$column]['model']);

            if (isset($this->_has_many[$column]['through']))
            {
                // Grab has_many "through" relationship table
                $through = $this->_has_many[$column]['through'];

                // Join on through model's target foreign key (far_key) and target model's primary key
                $join_col1 = $through.'.'.$this->_has_many[$column]['far_key'];
                $join_col2 = $model->_object_name.'.'.$model->_primary_key;

                $model->join($through)->on($join_col1, '=', $join_col2);

                // Through table's source foreign key (foreign_key) should be this model's primary key
                $col = $through.'.'.$this->_has_many[$column]['foreign_key'];
                $val = $this->pk();
            }
            else
            {
                // Simple has_many relationship, search where target model's foreign key is this model's primary key
                $col = $model->_object_name.'.'.$this->_has_many[$column]['foreign_key'];
                
                $val = null;
                if(isset($this->_has_many[$column]['near_key'])){
                    $val = $this->{$this->_has_many[$column]['near_key']};
                }else{
                    $val = $this->pk();    
                }
            }

            return $model->where($col, '=', $val)->find_all();
        }
        else
        {
            throw new Kohana_Exception('The :property property does not exist in the :class class',
                array(':property' => $column, ':class' => get_class($this)));
        }
    }

    /**
     * Returns the values of this object as an array, including any related one-one
     * models that have already been loaded using with()
     *
     * UPDATE: Now takes an optional argument, traverse, which contains the depth of 
     *         relations in which to traverse
     *
     * @author tspencer
     * @param  integer      $traverse   Depth of traversal to complete
     * @return array
     */
    public function as_array($traverse = 0)
    {
        $object = array();

        /* Get the columns related to the current model
        //---------------------------------------*/
        foreach ($this->_object as $column => $value)
        {
            // Call __get for any user processing
            $object[$column] = $this->__get($column);
        }

        if($traverse === 0) {
            /* Get the related objects that are already loaded
            //---------------------------------------*/
            foreach ($this->_related as $column => $model)
            {
                // Include any related objects that are already loaded
                $object[$column] = $model->as_array();
            }
        } else {

            /* Set and decrement the traverse var
            //---------------------------------------*/
            $traverse = filter_var($traverse, FILTER_SANITIZE_NUMBER_INT);
            $traverse--;

            /* Loop through "belongs to" relations
            //---------------------------------------*/
            foreach($this->_belongs_to as $column => $data) {

                if(isset($this->_related[$column])) {
                    /* If the column has already been fetched, use that model
                    //---------------------------------------*/
                    $model = $this->_related[$column];
                    $object[$column] = $model->as_array($traverse);
                } else {
                    /* Get the model and fetch the array
                    //---------------------------------------*/
                    $model = $this->__get($column);
                    $object[$column] = $model->as_array($traverse);
                }
            }

            /* Loop through "has many" relations
            //---------------------------------------*/
            foreach($this->_has_many as $column => $data) {
                if(isset($this->_related[$column])) {
                    /* If the column has already been fetched, use that model
                    //---------------------------------------*/
                    $model = $this->_related[$column];
                    $object[$column] = $model->as_array($traverse);
                } else {
                    $object[$column] = array();
                    /* Get the model and fetch the array
                    //---------------------------------------*/
                    $results = $this->__get($column);
                    foreach($results as $result) {
                        array_push($object[$column], $result->as_array($traverse));
                    }
                }
            }

            /* @TODO: Loop through 'has one' relationships
            //---------------------------------------*/

            /* @TODO: Loop through '_relation' relationships
            //---------------------------------------*/

        }

        return $object;
    }

    /**
     * Tests if this object has a relationship to a different model,
     * or an array of different models. When providing far keys, the number
     * of relations must equal the number of keys.
     * 
     *
     *     // Check if $model has the login role
     *     $model->has('roles', ORM::factory('role', array('name' => 'login')));
     *     // Check for the login role if you know the roles.id is 5
     *     $model->has('roles', 5);
     *     // Check for all of the following roles
     *     $model->has('roles', array(1, 2, 3, 4));
     *     // Check if $model has any roles
     *     $model->has('roles')
     *
     * @param  string  $alias    Alias of the has_many "through" relationship
     * @param  mixed   $far_keys Related model, primary key, or an array of primary keys
     * @return boolean
     */
    public function has($alias, $far_keys = NULL)
    {
        $count = $this->count_relations($alias, $far_keys);
        if ($far_keys === NULL)
        {
            return (bool) $count;
        }
        else
        {
            return $count >= count($far_keys);
        }

    }
}
class ORM_Relation_Exception extends Kohana_Exception{}