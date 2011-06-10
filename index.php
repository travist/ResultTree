<?php

require_once 'ResultTree.php';
require_once 'mockresults.php';

// Get the mock result.
$result = getResult();

// Create our tree...
$tree = new ResultTree($result);

// Print out the tree.
print_r($tree->getTree());

// Print out the tree with a filter.
print_r($tree->getTree(4));

// Now print out the flat results.
print_r($tree->getFlat(0, NULL));

// Now print out the flat results filtered.
print_r($tree->getFlat(4, NULL));
?>
