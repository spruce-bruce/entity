# Motivation
The decision to create this module was made both as a result of personal development preferences and Chapter 25 in the book [SQL Antipatterns](http://www.amazon.com/SQL-Antipatterns-Programming-Pragmatic-Programmers/dp/1934356557) by Bill Karwin. I will discuss here my reasons for wanting to split single-record code away from multi-record code as well as summarize the "Magic Beans" antipattern and why this module doesn't fully represent a solution to the problem posed in the "Magic Beans" chapter.

It's also worth noting that there is a widely supported belief that ORM itself is an antipattern that should be avoided. While I sympathize with this view, this module is not meant to solve the problems these people raise as the Entity module still makes heavy use of ORM.

## Entity: an object that truly represents one record
In Kohana ORM objects function in a number of different roles. The first role is to function as a query builder that performs interacts with the database and returns ORM objects, and the second is to simply represent a record in the databse and its relations as an object. The union of these two functions has always seemed clumsy to me. When using an ORM class as a model you end up with some methods that conform to the first role, i.e., they're using the query building and database methods to return ORM objects, and some methods will conform to the second role, i.e., they're modifying or performing some sort of processing on a single record and its relations.

The main problem I set out to solve with the entity module can be summarized thusly: How can I create a set of objects that will represent a single record that can't be used as a query builder that can be used in conjunction with kohana's ORM class to limit the amount of work necessary to make this separation possible.

The result is the Entity module which extends the functionality of Kohana's ORM module. Entity objects do not extend the ORM class, instead each Entity object has a reference to an ORM object. Some methods and property access calls are passed directly to the Entity's ORM object, and some are restricted. You can't call query builder methods on an Entity object, for example. 

## Magic Beans