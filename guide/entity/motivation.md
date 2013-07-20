# Motivation
The decision to create this module was made both as a result of personal development preferences and Chapter 25 in the book [SQL Antipatterns](http://www.amazon.com/SQL-Antipatterns-Programming-Pragmatic-Programmers/dp/1934356557) by Bill Karwin. I will discuss here my reasons for wanting to split single-record code away from multi-record code as well as summarize the "Magic Beans" antipattern and why this module doesn't fully represent a solution to the problem posed in the "Magic Beans" chapter.

It's also worth noting that there is a widely supported belief that ORM itself is an antipattern that should be avoided. While I sympathize with this view, this module is not meant to solve the problems these people raise as the Entity module still makes heavy use of ORM.

## Entity: an object that truly represents one record
In Kohana ORM objects function in a number of different roles. The first role is to function as a query builder that interacts with the database and returns ORM objects, and the second is to simply represent a record in the databse and its relations as an object. The union of these two functions has always seemed clumsy to me. When using an ORM class as a model you end up with some methods that conform to the first role, i.e., they're using the query building and database methods to return ORM objects, and some methods will conform to the second role, i.e., they're modifying or performing some sort of processing on a single record and its relations.

The main problem I set out to solve with the entity module can be summarized thusly: How can I create a set of objects that will represent a single record that can't be used as a query builder and can be used in conjunction with kohana's ORM class.

The result is the Entity module which extends the functionality of Kohana's ORM module. Entity objects do not extend the ORM class, instead each Entity object has a reference to an ORM object. Some methods and property access calls are passed directly to the Entity's ORM object, and some are restricted. You can't call query builder methods on an Entity object, for example. 

## Magic Beans
Magic Beans is the title of Chapter 25 of the excellent book [SQL Antipatterns](http://www.amazon.com/SQL-Antipatterns-Programming-Pragmatic-Programmers/dp/1934356557) by Bill Karwin. This chapter is devoted to the problems that come from using Active Record objects as models. Karwin covers four problems in this chapter which I will quickly summarize here.

1. Active Record couples models to schema
    - This may be the weaker of the four reasons because Kohana's ORM classes handle schema change quite well.
2. Active Record exposes CRUD
    - Because an ORM object is a query builder, any programmer with an ORM object can start making changes directly to the database. Kohana is flexible enough for us to limit this ourselves. The Entity module only just starts to address this, but not completely.
3. Active Record Encourages an Anemic Domain Model
    - Summarized, this problem is that active record models encourage using logic in controllers that is more appropriate in the model. The Entity module doesn't address this point.
4. Unit testing is hard
    - This is closely related to point 3. If you have code in controllers it's hard to unit test. It's also harder to unit test when you need a real database to test your objects.

Karwin's book goes into greater detail, and I'd encourage anybody who is skeptical to check it out. Suffice it to say, I generally agree with Karwin's assessment. Karwin suggests a design solution: rather than having a model that *is* an active record you have a model that *has* an active record. He goes into detail in the book and I won't go any further here.

The Entity module does *not* solve any of these four problems, it really only starts to address number 2 and number 4. There are two reasons for including this section. One is that the idea that an Entity *has* an ORM object comes from Karwin. The second is I'd like to further update this module, or extend this module with another, to fully implement Karwin's solution.