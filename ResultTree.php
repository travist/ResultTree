<?php
/**
 * The ResultTree class.
 *
 * Usage:  Given the following flat array structure.
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
 * It will build a tree structure from that flat data.
 *
 * $tree = new ResultTree($results);
 * print_r( $tree->getTree() );
 *
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

    // Null the orphan parents if no filter is provided.
    if (!$filterId) {
      $this->null_orphan_parents();
    }

    // Iterate through the results.
    foreach ($this->result as $index => $result) {
      $pid = $result->{$this->pid};
      $id = $result->{$this->id};

      if (!isset($flat[$id])) {
        // Create the item if it does not exist.
        $flat[$id] = new stdClass();
        $flat[$id]->children = array();
      }

      // Add the views index.
      $flat[$id]->data = $result;
      $flat[$id]->index = $index;

      // Add this to either the root tree, or the parent list.
      if ($pid) {
        if ($filterId == $pid) {
          $this->tree->children[$id] = &$flat[$id];
        }
        else {
          $flat[$pid]->children[$id] = &$flat[$id];
        }
      }
      else if (!$filterId) {
        $this->tree->children[$id] = &$flat[$id];
      }
    }

    // Return either the filtered result, or the whole result.
    return $this->tree;
  }

  /**
   * This will return just a structure of the id relationships of the tree.
   */
  public function getRelationships($filterId = 0) {
    $flat = array();
    $relationships = array();

    // Null the orphan parents if no filter is provided.
    if (!$filterId) {
      $this->null_orphan_parents();
    }

    // Iterate through the results.
    foreach ($this->result as $index => $result) {
      $pid = $result->{$this->pid};
      $id = $result->{$this->id};

      if (!isset($flat[$id])) {
        // Create the item if it does not exist.
        $flat[$id] = array();
      }

      // Add this to either the root tree, or the parent list.
      if ($pid) {
        if ($filterId == $pid) {
          $relationships[$id] = &$flat[$id];
        }
        else {
          $flat[$pid][$id] = &$flat[$id];
        }
      }
      else if (!$filterId) {
        $relationships[$id] = &$flat[$id];
      }
    }

    // Return either the filtered result, or the whole result.
    return $relationships;
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
    // Keep track of all ids added.
    static $ids = array();

    if (!empty($tree->children)) {
      
      // Iterate through all the children.
      foreach($tree->children as $id => $value) {

        // Check to make sure we haven't added this id before.
        // This will keep infinite recursion from occuring.
        if (!in_array($id, $ids)) {

          // Add this id to our array.
          $ids[] = $id;

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
  }
}
?>
