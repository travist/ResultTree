<?php
/**
 * ResultTree PHP class
 * Developed by Travis Tidwell - travis @ allplayers.com
 * License:  GPLv3
 * 
 * This class will take a flat array that has parent-child relationships
 * and builds a tree structure from that data.  For example, suppose you 
 * have a database that returns the following.
 * 
 *    Title     |      id       |      pid
 * ----------------------------------------------
 *   Item 1     |      1        |       0
 *   Item 2     |      2        |       1
 *   Item 3     |      3        |       1
 *   Item 4     |      4        |       2
 *   Item 5     |      5        |       4
 *   Item 6     |      6        |       0
 *   Item 7     |      7        |       6
 *   Item 8     |      8        |       6
 *   Item 9     |      9        |       8
 *   Item 10    |     10        |       8
 * 
 * This class would restructure this so that it would look like...
 * 
 * stdClass(
 *   children -> array(
 *     1 => stdClass(
 *       data => {ROW DATA},
 *       index => 0,
 *       children => array(
 *         2 => stdClass(
 *           data => {ROW DATA},
 *           index => 1,
 *           children => array(
 *             4 => stdClass(
 *               data => {ROW DATA},
 *               index => 3,
 *               children => array(
 *                 5 => stdClass(
 *                   data => {ROW DATA},
 *                   index => 4,
 *                   children => array()
 *                 )
 *               )
 *             )
 *           )
 *         ),
 *         3 => stdClass(
 *           data => {ROW DATA},
 *           index => 2,
 *           children => array()
 *         )
 *       )
 *     )
 *   ),
 *   6 => stdClass(
 *     data => {ROW DATA},
 *     index => 5,
 *     children => array(
 *       7 => stdClass(
 *         data => {ROW DATA},
 *         index => 6,
 *         children => array()
 *       ),
 *       8 => stdClass(
 *         data => {ROW DATA},
 *         index => 7,
 *         children => array(
 *           9 => stdClass(
 *             data => {ROW DATA},
 *             index => 8,
 *             children => array()
 *           ),
 *           10 => stdClass(
 *             data => {ROW DATA},
 *             index => 9,
 *             children => array()
 *           )
 *         )
 *       )
 *     )
 *   ) 
 * );
 */
class ResultTree {
  
  // The original result.
  private $result = NULL;
  
  // The name of the parent id field.
  private $pid = 'pid';
  
  // The name of the main id field.
  private $id = 'id';
  
  // The number of results.
  private $num_results = 0;
  
  // The tree object.
  private $tree = NULL;
  
  /**
   * Constructor for the Result tree.
   * 
   * @param type $result
   * @param type $id
   * @param type $pid 
   */
  public function __construct($result, $id = 'id', $pid = 'pid') {
    $this->result = $result;
    $this->id = $id;
    $this->pid = $pid;
    $this->num_results = count($result);
    $this->null_orphan_parents();
  }
  
  /**
   * This will return the results array as a structured TREE.
   * 
   * @param type $filterId
   * @return A structured tree array with the following signature.
   * 
   * array(
   *   pid => stdClass(
   *     data => {THE ORIGINAL ROW DATA},
   *     index => {THE ORIGINAL ROW INDEX},
   *     children => array(
   *       {ALL CHILDREN IN THE SAME STRUCTURE}
   *     )
   *   )
   * )
   */ 
  public function getTree($filterId = 0) {
    $flat = array();
    $this->tree = new stdClass();
    $filter = NULL;

    // Iterate through the results.
    foreach ($this->result as $index => $result) {    
      $pid = $result->{$this->pid};
      $id = $result->{$this->id};

      if (!isset($flat[$id])) {
        // Create the item if it does not exist.
        $flat[$id] = new stdClass();
        $flat[$id]->children = array();
      }

      // Check for our filter.
      if( !$filter && ($filterArg == $id)) {
        $filter = &$flat[$id];
      } 

      // Add the views index.
      $flat[$id]->data = $result;
      $flat[$id]->index = $index;

      // Add this to either the root tree, or the parent list.
      if ($pid) {
        $flat[$pid]->children[$id] = &$flat[$id];
      } else {
        $this->tree->children[$id] = &$flat[$id];
      }
    }

    // Set the tree based on if they provided a filter or not.
    $this->tree = $filter ? $filter : $this->tree;
    
    // Return either the filtered result, or the whole result.
    return $this->tree;    
  }
  
  /**
   * This will return flat results, but in the order of the 
   * tree structure.
   * 
   * @param type $filterId 
   */
  public function getFlat($filterId = 0, $reset = FALSE) {
    $this->tree = (!$reset && $this->tree) ? $this->tree : $this->getTree($filterId);
    $flat = array();
    $this->_getFlat($this->tree, $flat);
    return $flat;   
  }  
  
  /**
   * Will take a views result and NULL out the orphans parents.
   */
  private function null_orphan_parents() {
    
    // Iterate through all of our results.
    for($i=0; $i < $this->num_results; $i++ ) {
      
      // If there is a parent item.
      if( $this->result[$i]->{$this->pid} ) {
        
        $found = FALSE;
        
        // Reiterate over the array and try to locate this parent item.
        for($j=0; $j < $this->num_results; $j++ ) {
          
          // Check if we found this parent.
          if( $this->result[$j]->{$this->id} == $this->result[$i]->{$this->pid} ) {
            $found = TRUE;
            break;
          }
        }
        
        // If it was not found...
        if( !$found ) {
          
          // Null out the parent id so that we can build an acurate tree.
          $this->result[$i]->{$this->pid} = NULL;
        }
      }
    }    
  }
  
  /**
   * Recursive function to flatten a tree structure.
   * 
   * @param type $tree
   * @param type $flat 
   */
  private function _getFlat($tree, &$flat) {
    // Iterate through all the children.
    foreach($tree->children as $id => $value) {

      // Add that result to the items.
      $flat[] = $this->result[$value->index];

      // If this has a child, then.
      if ($value->children) {

        // Normalize the children.
        $this->_getFlat($value, $flat);
      }
    }  
  }
}
?>
