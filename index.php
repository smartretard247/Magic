<?php #$root = filter_input(INPUT_SERVER, 'DOCUMENT_ROOT'); //get root folder for relative paths
  $lifetime = 60 * 60 * 24; //24 hours.
  ini_set('session.use_only_cookies', true);
  ini_set('session.gc_probability', 1);
  ini_set('session.gc_divisor', 100);
  session_set_cookie_params($lifetime, '/'); //all paths, must be called before session_start()
  session_save_path(filter_input(INPUT_SERVER, 'DOCUMENT_ROOT') . '/sessions'); session_start();

  #date_default_timezone_set('America/New_York');
  date_default_timezone_set('Japan');

  #$_SESSION['rootDir'] = "/";
  $_SESSION['rootDir'] = "";
  
  function DisplayMessage($message, $error = false) {
    echo '<b style="color: ';
    echo $error ? 'red' : 'green';
    echo ';">' . $message . '</b>';
  }
  
  include_once $_SESSION['rootDir'] . '../database.php'; $db = new Database('mtg');
  
  function getCard($name) {
    global $db;
    $queryGetCardId = "SELECT * FROM cards WHERE CardName = '$name'";
    $qgcid = $db->query($queryGetCardId);
    $found = $qgcid->fetchAll();
    return $found[0];
  }
  
  function getCardById($id) {
    global $db;
    $queryGetCardId = "SELECT * FROM cards WHERE ID = '$id'";
    $qgcid = $db->query($queryGetCardId);
    $found = $qgcid->fetchAll();
    return $found[0];
  }
  
  function getOwner($deckId) {
    global $db;
    $queryGetCardId = "SELECT PlayerID FROM decks WHERE DeckID = '$deckId'";
    $qgcid = $db->query($queryGetCardId);
    if($qgcid) {
      $found = $qgcid->fetch();
      return $found[0]['PlayerID'];
    } else {
      return null;
    }
  }
  
  $new = filter_input(INPUT_GET, 'new');
  $removeCard = filter_input(INPUT_POST, 'remove');
  if(isset($removeCard)) {
    $new = 1;
    $removedCard = filter_input(INPUT_POST, 'CardName');
    //then remove the card ($removeCard is the ID of the card to remove
    $exec = "DELETE FROM decks WHERE ID = $removeCard";
    if($db->exec($exec) > 0) {
      DisplayMessage("Successfully removed '$removedCard' from deck.", false);
    } else {
      DisplayMessage("Error removing from deck.", true);
    }
  } else {
    $addCard = filter_input(INPUT_POST, 'add');
    if(isset($addCard)) {
      $new = 1;
      $ignoreCorrection = filter_input(INPUT_POST, 'IgnoreCorrection');
      $deckID = filter_input(INPUT_POST, 'DeckID');
      $colorID = filter_input(INPUT_POST, 'ColorID');
      $typeID = filter_input(INPUT_POST, 'TypeID');
      $quantity = filter_input(INPUT_POST, 'Quantity');
      $image = filter_input(INPUT_POST, 'Image');
      $cardName = filter_input(INPUT_POST, 'CardName');
      
      $playerID = getOwner($deckID);
      if(!$playerID) {
        $playerID = filter_input(INPUT_POST, 'PlayerID');
      }
      
      if(!getCard($cardName)) {
        $likeString = substr($cardName, 0, 3) . "%";
        $queryS = "SELECT ID, CardName, ColorID, TypeID, Image FROM cards WHERE CardName LIKE '$likeString'";
        $qs = $db->query($queryS);
        $foundS = $qs->fetchAll();
      }
      
      if($foundS) {
        if(sizeof($foundS) > 1) {
          if($ignoreCorrection == null) {
            $ignoreCorrection = -2;
            echo '<table class="floater">';
            echo '<tr><th colspan="2">Did you mean?</th></tr>';
            foreach($foundS as $similarCard) {
              echo '<tr><td>' . $similarCard['CardName'] . '?</td><td>';
              echo '<form method="POST">';
              echo '<input name="CardID" value="' . $similarCard['ID'] . '" type="hidden"/>';
              echo '<input name="DeckID" value="' . $deckID . '" type="hidden"/>';
              echo '<input name="ColorID" value="' . $similarCard['ColorID'] . '" type="hidden"/>';
              echo '<input name="TypeID" value="' . $similarCard['TypeID'] . '" type="hidden"/>';
              echo '<input name="Quantity" value="' . $quantity . '" type="hidden"/>';
              echo '<input name="PlayerID" value="' . $playerID . '" type="hidden"/>';
              echo '<input name="Image" value="' . $similarCard['Image'] . '" type="hidden"/>';
              echo '<input name="CardName" value="' . $similarCard['CardName'] . '" type="hidden"/>';
              echo '<input type="hidden" value="1" name="add"/>';
              echo '<input name="IgnoreCorrection" value="-1" type="hidden"/>';
              echo '<input value="Yes" type="button" onclick="submit();"></input>';
              echo '</form></td></tr>';
            }
            echo '<tr><td>' . $cardName . '?</td><td>';
            echo '<form method="POST">';
            echo '<input name="DeckID" value="' . $deckID . '" type="hidden"/>';
            echo '<input name="ColorID" value="' . $colorID . '" type="hidden"/>';
            echo '<input name="TypeID" value="' . $typeID . '" type="hidden"/>';
            echo '<input name="Quantity" value="' . $quantity . '" type="hidden"/>';
            echo '<input name="PlayerID" value="' . $playerID . '" type="hidden"/>';
            echo '<input name="Image" value="' . $image . '" type="hidden"/>';
            echo '<input name="CardName" value="' . $cardName . '" type="hidden"/>';
            echo '<input type="hidden" value="1" name="add"/>';
            echo '<input name="IgnoreCorrection" value="1" type="hidden"/>';
            echo '<input value="Yes" type="button" onclick="submit();"/>';
            echo '</form>';
            echo '</td></tr></table>';
          } else {
            $ignoreCorrection = null;
          }
        } else if($foundS[0]['CardName'] == $cardName) {
          $ignoreCorrection = null;
        }
      }
       
      $overrideID = filter_input(INPUT_POST, 'CardID');
      if($overrideID) {
        $card = getCardById($overrideID);
        $cardID = $card['ID'];
        $ignoreCorrection = null;
      } else {
        $card = getCard($cardName);
        $cardID = $card['ID'];
      }
      
      if($cardID) {
        $colorID = $card['ColorID'];
        $typeID = $card['TypeID'];
        $image = $card['Image'];
      }
      
      if(!$cardID && $colorID && $typeID && $cardName && !$ignoreCorrection) {
        if(!$image) {
          $corrected = str_replace(" ","_",$cardName);
          $corrected2 = str_replace(",","",$corrected);
          $image = $corrected2 . ".png";
        }

        if(!$similarCardExists || ($similarCardExists && $ignoreCorrection == 1)) {
          $exec = "INSERT INTO cards (CardName, ColorID, TypeID, Image) VALUES "
                  . "('$cardName', $colorID, $typeID, 'images/decks/$image')";
          if($db->exec($exec) > 0) {
            DisplayMessage("Successfully created new card.  ", false);
            $card = getCard($cardName);
            $cardID = $card['ID'];
            if($cardID) {
              $colorID = $card['ColorID'];
              $typeID = $card['TypeID'];
              $image = $card['Image'];
            }
          } else {
            DisplayMessage("Failed card creation: ($cardName, $colorID, $typeID, $image)  ", true);
          }
        }
      }
      
      if($deckID && $cardID && $quantity && $playerID && $ignoreCorrection == null) {
        $queryI = "SELECT ID FROM decks WHERE DeckID = $deckID AND CardID = $cardID";
        $qi = $db->query($queryI);
        $found = $qi->fetch();
        $existsInDeck = $found['ID'];
        
        if($existsInDeck) {
          $exec = "UPDATE decks SET Quantity = Quantity + $quantity WHERE ID = $existsInDeck";
        } else {
          $exec = "INSERT INTO decks (DeckID, CardID, Quantity, PlayerID) VALUES "
                  . "($deckID, $cardID, $quantity, $playerID)";
        }
        
        
        if($db->exec($exec) > 0) {
          DisplayMessage("Successfully added card to decks.  ", false);
        } else {
          DisplayMessage("Execution returned 0 affected rows: (DeckID = $deckID, CardID = $cardID, Quantity = $quantity, PlayerID = $playerID)", true);
        }
      } else if($ignoreCorrection) {
        // do nothing
      } else {
        DisplayMessage("Invalid data: (DeckID = $deckID, CardID = $cardID, Quantity = $quantity, PlayerID = $playerID)  ", true);
      }
    }
  }
  
  if($new) {
    $queryN = "SELECT CardName FROM cards ORDER BY CardName";
    $qn = $db->query($queryN);
    $cardNames = $qn->fetchAll();
    
    $queryC = "SELECT * FROM colors";
    $qc = $db->query($queryC);
    $colors = $qc->fetchAll();

    $queryT = "SELECT * FROM types";
    $qt = $db->query($queryT);
    $types = $qt->fetchAll();

    $queryP = "SELECT * FROM players";
    $qp = $db->query($queryP);
    $players = $qp->fetchAll();
    
    $queryD = "SELECT DISTINCT DeckID AS ID FROM decks ORDER BY DeckID";
    $qd = $db->query($queryD);
    $deckIds = $qd->fetchAll();

    $sort = filter_input(INPUT_GET, 'sort');
    $desc = filter_input(INPUT_GET, 'desc');
    if($sort) {
      $_SESSION['Sort'] = $sort;
    }
    if($desc) {
      $_SESSION['Desc'] = $desc;
    }
    switch ($_SESSION['Sort']) {
      case 'd': $by = 'DeckID'; break;
      case 'n': $by = 'CardName'; break;
      case 'c': $by = 'Color'; break;
      case 't': $by = 'Type'; break;
      case 'q': $by = 'Quantity'; break;
      case 'p': $by = 'Player'; break;
      default: $by = 'DeckID';
    }

    $query = "SELECT d.ID, d.DeckID, c.CardName, "
            . "(SELECT Color FROM colors WHERE ID = c.ColorID) AS Color, "
            . "(SELECT Type FROM types WHERE ID = c.TypeID) AS Type, d.Quantity, "
            . "(SELECT Player FROM players WHERE ID = d.PlayerID) AS Player, c.Image FROM decks d "
            . "LEFT JOIN cards c ON d.CardID = c.ID ORDER BY $by";
    if($_SESSION['Desc'] == 1) {
      $query .= ' DESC';
    }
    $q = $db->query($query);
    if($q !== FALSE) {
      $decks = $q->fetchAll();
    } else {
      echo "ERROR: $query";
    }
  }
?>

<!DOCTYPE html>
<html>
  <head>
    <title>Magic: The Gathering</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="resources/magic.css" />
    <script src="resources/utils.js"></script>
    <script src="resources/flip-card.js"></script>
    <script src="resources/functions.js"></script>
    <script type="text/javascript">
      function setName() {
        var cardName = document.getElementById('cardName');
        var cardPicker = document.getElementById('cardPicker');
        cardName.value = cardPicker.value;
      }
      
      function init() {
        document.getElementById("Quantity").addEventListener("keyup", function(event) {
          event.preventDefault();
          if (event.keyCode === 13) {
            document.getElementById("adder").submit();
          }
        });
        
        document.getElementById("cardPicker").addEventListener("keyup", function(event) {
          event.preventDefault();
          if (event.keyCode === 13) {
            document.getElementById("adder").submit();
          }
        });
      }
    </script>
  </head>
  <body onload="init();">
    <?php if($ignoreCorrection != -2) : ?>
      <div id="content">
        <table id="main-table">
          <?php if(!$new) : ?>
            <tr>
              <td class="ring-container">
                <table id="ring-table-left" class="ring-table"></table>
              </td>
              <td style="padding-bottom: 20px;">
                <table class="inner-table">
                  <tr>
                    <td class="health-column">
                      <table><tr><td><button id="player1up3" class="buttons">3+</button></td></tr>
                      <tr><td><button id="player1up" class="buttons">+</button></td></tr></table>
                    </td>
                    <td class="container" rowspan="3" colspan="2">
                      <div id="card">
                        <figure class="front" id="cardFront"><p class="magic18" id="word"></p><p class="magic18" id="description"></p></figure>
                        <figure class="back"></figure>
                      </div>
                    </td>
                    <td class="health-column">
                      <table><tr><td><button id="player2up3" class="buttons">3+</button></td></tr>
                      <tr><td><button id="player2up" class="buttons">+</button></td></tr></table>
                    </td>
                  </tr>
                  <tr>
                    <td class="health-column">
                      <img id="player1health" src="images/pone20.png" alt="20 Life"/>
                    </td>
                    <td class="health-column">
                      <img id="player2health" src="images/ptwo20.png" alt="20 Life"/>
                    </td>
                  </tr>
                  <tr>
                    <td class="health-column">
                      <table><tr><td><button id="player1down" class="buttons">=</button></td></tr>
                      <tr><td><button id="player1down3" class="buttons">3=</button></td></tr></table>
                    </td>
                    <td class="health-column">
                      <table><tr><td><button id="player2down" class="buttons">=</button></td></tr>
                      <tr><td><button id="player2down3" class="buttons">3=</button></td></tr></table>
                    </td>
                  </tr>
                  <tr >
                    <td>

                    </td>
                    <td>
                      <button id="roll" class="buttons">Roll</button>
                    </td>
                    <td>
                      <button id="reset" class="buttons">Reset</button>
                    </td>
                    <td>

                    </td>
                  </tr>
                </table>
              </td>
              <td class="ring-container">
                <table id="ring-table-right" class="ring-table"></table>
              </td>
            </tr>
          <?php else : ?>
            <tr>
              <td colspan="6">
                <table  class="inner-table">
                  <tr>
                    <th><a href="?new=1&sort=d">DeckID</a><a href="?new=1&sort=d&desc=1"> v </a></th>
                    <th><a href="?new=1&sort=p">Player</a><a href="?new=1&sort=p&desc=1"> v </a></th>
                    <th><a href="?new=1&sort=t">Type</a><a href="?new=1&sort=t&desc=1"> v </a></th>
                    <th><a href="?new=1&sort=c">Color</a><a href="?new=1&sort=c&desc=1"> v </a></th>
                    <th><a href="?new=1&sort=n">Card Name</a><a href="?new=1&sort=n&desc=1"> v </a></th>
                    <th><a href="?new=1&sort=q">Quantity</a><a href="?new=1&sort=q&desc=1"> v </a></th>
                    <th><a>Image</a></th>
                    <th><a>[ +/- ]</a></th>
                  </tr>
                  <tr>
                    <form id="adder" name="adder" method="POST">

                    <td style="text-align: center;">
                      <select name="DeckID">
                        <?php foreach($deckIds as $row) : ?>
                          <option <?php if($deckID == $row['ID']) { echo ' selected '; } ?> value="<?php echo $row['ID']; ?>"><?php echo $row['ID']; ?></option>
                        <?php endforeach; ?>
                        <option <?php if(!$deckID) { echo ' selected '; } ?> value="<?php echo $row['ID'] + 1; ?>"><?php echo $row['ID'] + 1; ?></option>
                      </select>
                    </td>

                    <td style="text-align: center;">
                      <select name="PlayerID">
                        <?php foreach($players as $row) : ?>
                          <option <?php if($playerID == $row['ID']) { echo ' selected '; }; ?> value="<?php echo $row['ID']; ?>"><?php echo $row['Player']; ?></option>
                        <?php endforeach; ?>
                      </select>
                    </td>

                    <td style="text-align: center;">
                      <select name="TypeID">
                        <?php foreach($types as $row) : ?>
                          <option <?php if($typeID == $row['ID']) { echo ' selected '; }; ?> value="<?php echo $row['ID']; ?>"><?php echo $row['Type']; ?></option>
                        <?php endforeach; ?>
                      </select>
                    </td>

                    <td style="text-align: center;">
                      <select name="ColorID">
                        <?php foreach($colors as $row) : ?>
                          <option <?php if($colorID == $row['ID']) { echo ' selected '; }; ?> value="<?php echo $row['ID']; ?>"><?php echo $row['Color']; ?></option>
                        <?php endforeach; ?>
                      </select>
                    </td>

                    <td>
                      <input id="cardName" type="text" size="25" name="CardName" autofocus/>
                      <select id="cardPicker" onchange="setName();">
                        <option></option>
                        <?php foreach ($cardNames as $c) : ?>
                          <option><?php echo $c['CardName']; ?></option>
                        <?php endforeach; ?>
                      </select>
                    </td>

                    <td style="text-align: center;">
                      <select name="Quantity" id="Quantity">
                        <?php for($i = 1; $i <= 20; $i++) : ?>
                          <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                        <?php endfor; ?>
                      </select>
                    </td>

                    <td>
                      <input type="file" name="Image"/>
                    </td>

                    <td style="text-align: center;">
                      <input type="hidden" value="1" name="add"/>
                      <input type="submit" value="+">
                    </td>

                    </form>
                  </tr>
                  <?php if($decks) : ?>
                    <?php foreach($decks as $row) : ?>
                      <tr>
                        <td style="text-align: center;"><?php echo $row['DeckID']; ?></td>
                        <td style="text-align: center;"><?php echo $row['Player']; ?></td>
                        <td style="text-align: center;"><?php echo $row['Type']; ?></td>
                        <td style="text-align: center;"><?php echo $row['Color']; ?></td>
                        <td><?php echo $row['CardName']; ?></td>
                        <td style="text-align: center;"><?php echo $row['Quantity']; ?></td>
                        <td style="text-align: center;"><?php echo $row['Image']; ?></td>
                        <td style="text-align: center;">
                          <form name="remover" method="POST">
                            <input type="button" value="-" onclick="submit();"/>
                            <input type="hidden" value="<?php echo $row['ID']; ?>" name="remove"/>
                            <input type="hidden" value="<?php echo $row['CardName']; ?>" name="CardName"/>
                          </form>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else : ?>
                    <tr><td colspan="8"><b>No data exists in the table.</b></td></tr>
                  <?php endif; ?>
                </table>
              </td>
            </tr>
          <?php endif; ?>
        </table>
      </div>
    <?php endif; ?>
    
    <div class="floater">
      <a href="cardIndex.php?new=1">Card Index</a>
    </div>
    <div class="floater">
      <a href="http://gatherer.wizards.com/pages/Advanced.aspx" target="_blank">Gatherer</a>
    </div>
  </body>
</html>
