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
    $exec = "DELETE FROM cards WHERE ID = $removeCard";
    if($db->exec($exec) > 0) {
      DisplayMessage("Successfully deleted '$removedCard'.", false);
    } else {
      DisplayMessage("Error deleting card.", true);
    }
  } else {
    $addCard = filter_input(INPUT_POST, 'add');
    if(isset($addCard)) {
      $new = 1;
      $colorID = filter_input(INPUT_POST, 'ColorID');
      $typeID = filter_input(INPUT_POST, 'TypeID');
      $image = filter_input(INPUT_POST, 'Image');
      $cardName = filter_input(INPUT_POST, 'CardName');
      
      $card = getCard($cardName);
      $cardID = $card['ID'];
      if($cardID) {
        $colorID = $card['ColorID'];
        $typeID = $card['TypeID'];
        $image = $card['Image'];
      }
      
      if(!$cardID && $colorID && $typeID && $cardName) {
        if(!$image) {
          $corrected = str_replace(" ","_",$cardName);
          $corrected2 = str_replace(",","",$corrected);
          $image = $corrected2 . ".png";
        }
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

    $sort = filter_input(INPUT_GET, 'sort');
    $desc = filter_input(INPUT_GET, 'desc');
    if($sort) {
      $_SESSION['Sort'] = $sort;
    }
    if($desc) {
      $_SESSION['Desc'] = $desc;
    }
    switch ($_SESSION['Sort']) {
      case 'i': $by = 'ID'; break;
      case 'n': $by = 'CardName'; break;
      case 'c': $by = 'Color'; break;
      case 't': $by = 'Type'; break;
      default: $by = 'CardName';
    }

    $query = "SELECT ID, CardName, "
            . "(SELECT Color FROM colors WHERE ID = ColorID) AS Color, "
            . "(SELECT Type FROM types WHERE ID = TypeID) AS Type, "
            . "Image FROM cards ORDER BY $by";
    if($_SESSION['Desc'] == 1) {
      $query .= ' DESC';
    }
    $q = $db->query($query);
    $cards = $q->fetchAll();
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
    <div id="content">
      <table id="main-table">
        <tr>
          <td colspan="6">
            <table class="inner-table">
              <tr>
                <th><a href="?new=1&sort=i">ID</a><a href="?new=1&sort=i&desc=1"> v </a></th>
                <th><a href="?new=1&sort=t">Type</a><a href="?new=1&sort=t&desc=1"> v </a></th>
                <th><a href="?new=1&sort=c">Color</a><a href="?new=1&sort=c&desc=1"> v </a></th>
                <th><a href="?new=1&sort=n">Card Name</a><a href="?new=1&sort=n&desc=1"> v </a></th>
                <th><a>Image</a></th>
                <th><a>[ +/- ]</a></th>
              </tr>
              <tr>
                <form id="adder" name="adder" method="POST">

                <td style="text-align: center;">
                  --
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

                <td>
                  <input type="file" name="Image"/>
                </td>

                <td style="text-align: center;">
                  <input type="hidden" value="1" name="add"/>
                  <input type="submit" value="+">
                </td>

                </form>
              </tr>
              <?php if($cards) : ?>
                <?php foreach($cards as $row) : ?>
                  <tr>
                    <td style="text-align: center;"><?php echo $row['ID']; ?></td>
                    <td style="text-align: center;"><?php echo $row['Type']; ?></td>
                    <td style="text-align: center;"><?php echo $row['Color']; ?></td>
                    <td><?php echo $row['CardName']; ?></td>
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
                <tr><td colspan="5"><b>No data exists in the table.</b></td></tr>
              <?php endif; ?>
            </table>
          </td>
        </tr>
      </table>
    </div>
    
    <div class="floater">
      <a href="index.php?new=1">Deck Index</a>
    </div>
    <div class="floater">
      <a href="http://gatherer.wizards.com/pages/Advanced.aspx" target="_blank">Gatherer</a>
    </div>
  </body>
</html>
