<?php

// Create connection
$con=mysqli_connect("192.168.1.100:3307","Jeezy","BLiss20106=","mtg");
 
// Check connection
if (mysqli_connect_errno()) {
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
}

$deckId = filter_input(INPUT_GET, "did");

if (!empty($deckId)) {
  $sql = "SELECT ID, DeckID, CardID, PlayerID, Quantity FROM decks WHERE DeckID = '$deckId'";
} else {
  $sql = "SELECT ID, DeckID, CardID, PlayerID, Quantity FROM decks";
}

// Check if there are results
$result = mysqli_query($con, $sql);
if ($result) {
  // If so, then create a results array and a temporary one
  // to hold the data
  $resultArray = array();
  $tempArray = array();

  // Loop through each row in the result set
  while($row = $result->fetch_object()) {
    // Add each row into our results array
    $tempArray = $row;
    array_push($resultArray, $tempArray);
  }

  // Finally, encode the array to JSON and output the results
  echo json_encode($resultArray);
}
 
// Close connections
mysqli_close($con);
