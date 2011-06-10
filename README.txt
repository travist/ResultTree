ResultTree PHP class
Developed by Travis Tidwell - travis @ allplayers.com
License:  GPLv3

This class will take a flat array that has parent-child relationships
and builds a tree structure from that data.  For example, suppose you 
have a database that returns the following.

   Title     |      id       |      pid
----------------------------------------------
  Item 1     |      1        |       0
  Item 2     |      2        |       1
  Item 3     |      3        |       1
  Item 4     |      4        |       2
  Item 5     |      5        |       4
  Item 6     |      6        |       0
  Item 7     |      7        |       6
  Item 8     |      8        |       6
  Item 9     |      9        |       8
  Item 10    |     10        |       8

This class would restructure this so that it would look like...

stdClass(
  children -> array(
    1 => stdClass(
      data => {ROW DATA},
      index => 0,
      children => array(
        2 => stdClass(
          data => {ROW DATA},
          index => 1,
          children => array(
            4 => stdClass(
              data => {ROW DATA},
              index => 3,
              children => array(
                5 => stdClass(
                  data => {ROW DATA},
                  index => 4,
                  children => array()
                )
              )
            )
          )
        ),
        3 => stdClass(
          data => {ROW DATA},
          index => 2,
          children => array()
        )
      )
    )
  ),
  6 => stdClass(
    data => {ROW DATA},
    index => 5,
    children => array(
      7 => stdClass(
        data => {ROW DATA},
        index => 6,
        children => array()
      ),
      8 => stdClass(
        data => {ROW DATA},
        index => 7,
        children => array(
          9 => stdClass(
            data => {ROW DATA},
            index => 8,
            children => array()
          ),
          10 => stdClass(
            data => {ROW DATA},
            index => 9,
            children => array()
          )
        )
      )
    )
  ) 
);