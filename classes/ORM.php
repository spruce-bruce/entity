<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Extending ORM to return Entity objects where available from find() and 
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
         * on the model name. Model_Event means we're looking for the Event
         * entity, etc.
         */
        if(!$this->_entity){
            $class = get_called_class();
            $class_parts = explode("_", strtolower($class));

            $class_type = array_shift($class_parts); // Expect this to be 'Model'
            $class_path = implode('/', $class_parts);
            $entity_name = "Entity_" . $class_parts[count($class_parts) - 1];
            
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
}