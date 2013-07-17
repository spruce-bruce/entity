# Motivation
The decision to create this module was made both as a result of personal development preferences and Chapter 25 in the book [SQL Antipatterns](http://www.amazon.com/SQL-Antipatterns-Programming-Pragmatic-Programmers/dp/1934356557) by Bill Karwin. I will discuss here my reasons for wanting to split single-record code away from multi-record code as well as summarize the "Magic Beans" antipattern and why this module doesn't fully represent a solution to the problem posed in the "Magic Beans" chapter.

It's also worth noting that there is a widely supported belief that ORM itself is an antipattern that should be avoided. While I sympathize with this view, this module is not meant to solve the problems these people raise as the Entity module still makes heavy use of ORM.

## Entity: an object that truly represents one record
In Kohana ORM objects function in a number of different roles. 

## Magic Beans