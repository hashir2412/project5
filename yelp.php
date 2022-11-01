<?php
//https://stackoverflow.com/questions/4746079/how-to-create-a-html-table-from-a-php-array
//https://www.w3schools.com/howto/tryit.asp?filename=tryhow_css_table_side_by_side&stacked=h
session_start();
$favorites = array();
$result = new stdClass();
$result->businesses = array();
if (isset($_SESSION["search"])) {
    $result->businesses = $_SESSION["search"];
}
$API_KEY = 'SbG6yG74aKQBBzpP_5vFg_eG_VyCjS8V7uSXGU2o_UpmaY03rCxhvmR8pFmQWPNFZRaEL7B_yAll7OegOaCuBwQRnn3qkRPAbZZjGDpw_poouudQ4adzFdb_HD0zY3Yx';
if (isset($_GET['city']) && isset($_GET['keywords'])) {
    $city = htmlentities($_GET['city']);
    $search = htmlentities($_GET['keywords']);
    makeApiCall($city, $search);
    getFavorites();
} else if (isset($_GET['store'])) {
    $image_url = $_GET['store'];
    addToFavorite($image_url);
    getFavorites();
} else {
    session_unset();
    global $favorites;
    $favorites = array();
    $result->businesses = array();
    getFavorites();
}
function getFavorites()
{
    global $favorites;
    $favorites  = array();
    $dbh = new PDO(
        "mysql:host=127.0.0.1:3306;dbname=yelp",
        "root",
        "",
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );
    $dbh->beginTransaction();
    $stmt = $dbh->prepare('select * from favorites');
    $stmt->execute();
    while ($row = $stmt->fetch()) {
        $obj = new stdClass();
        $obj->id = $row["id"];
        $obj->name = $row["name"];
        $obj->image_url = $row["image_url"];
        $obj->yelp_page_url = $row["yelp_page_url"];
        $obj->categories = $row["categories"];
        $obj->price = $row["price"];
        $obj->rating = $row["rating"];
        $obj->address = $row["address"];
        $obj->phone = $row["phone"];
        global $favorites;
        array_push($favorites, $obj);
    }
    global $favorites;
}
function addToFavorite($business_id)
{
    getFavorites();
    global $favorites;
    global $result;
    $isMatch = false;
    foreach ($favorites as $value) {
        if ($value->id === $business_id) {
            $isMatch = true;
        }
    }
    if (!$isMatch) {
        $copy = new stdClass();
        foreach ($result->businesses as $value) {
            if ($value->id === $business_id) {
                $copy = clone $value;
            }
        }
        array_push($favorites, $copy);
        $dbh = new PDO(
            "mysql:host=127.0.0.1:3306;dbname=yelp",
            "root",
            "",
            array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
        );
        $dbh->beginTransaction();
        $cat = "";
        foreach ($copy->categories as $key) {
            $cat = $cat . $key->title;
            # code...
        }
        $dbh->exec('Insert into favorites(id, name, image_url, yelp_page_url, categories, price, rating, address, phone) 
        VALUES (' . '"' . $copy->id . '","' . $copy->name . '","' . $copy->image_url . '","' . $copy->url . '","' . $cat . '","' . $copy->price . '","' . $copy->rating . '","' . join(", ", $copy->location->display_address) . '","' . $copy->phone . '"' . ')');
        $dbh->commit();
    }
}



function makeApiCall($city, $search)
{
    // put your Yelp API key here:
    $_SESSION["city"] = $city;
    $_SESSION["searchTxt"] = $search;
    $API_HOST = "https://api.yelp.com";
    $SEARCH_PATH = "/v3/businesses/search";
    $BUSINESS_PATH = "/v3/businesses/";
    $curl = curl_init();
    if (FALSE === $curl)
        throw new Exception('Failed to initialize');
    $url = $API_HOST . $SEARCH_PATH . "?" . "location=" . $city . "&term=" . $search . "&limit=10";
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,  // Capture response.
        CURLOPT_ENCODING => "",  // Accept gzip/deflate/whatever.
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            "authorization: Bearer " . $GLOBALS['API_KEY'],
            "cache-control: no-cache",
        ),
    ));
    $response = curl_exec($curl);
    global $result;
    $result = json_decode($response);
    $_SESSION["search"] = $result->businesses;
    curl_close($curl);
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Document</title>
    <style>
        * {
            box-sizing: border-box;
        }

        .row {
            margin-left: -5px;
            margin-right: -5px;
        }

        .column {
            float: left;
            width: 50%;
            padding: 5px;
        }

        /* Clearfix (clear floats) */
        .row::after {
            content: "";
            clear: both;
            display: table;
        }

        table {
            border-collapse: collapse;
            border-spacing: 0;
            width: 100%;
            border: 1px solid #ddd;
        }

        th,
        td {
            text-align: left;
            padding: 16px;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
    </style>
</head>

<body>
    <!-- <form action="yelp.php?city=<?= isset($_SESSION["city"]) ? $_SESSION["city"] : '' ?>&keywords=<?= isset($_SESSION["searchTxt"]) ? $_SESSION["searchTxt"] : '' ?>" method="GET"> -->
    <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="GET">
        <label>City: <input name="city" id="city" type="text" value="<?php echo (isset($_SESSION["city"])) ? $_SESSION["city"] : ''; ?>" /></label>
        <label>Search: <input name="keywords" id="keywords" type="text" value="<?php echo (isset($_SESSION["searchTxt"])) ? $_SESSION["searchTxt"] : ''; ?>" /></label>
        <button type="submit">Find</button>
        <!-- <a href="yelp.php?city=<?= isset($_SESSION["city"]) ? $_SESSION["city"] : '' ?>&keywords=<?= isset($_SESSION["searchTxt"]) ? $_SESSION["searchTxt"] : '' ?>"  -->
    </form>
    <a href="yelp.php?reset"><input type="submit" id="reset" name="reset" value="reset" /></a>

    <div class="row">
        <div class="column">
            <table>
                <tr>
                    <th>Search Results</th>
                </tr>
                <?php foreach ($result->businesses as $key) : ?>
                    <tr>
                        <td>
                            <div>
                                <a href="yelp.php?store=<?= $key->id; ?>">
                                    <img src="<?= $key->image_url; ?>" height="100px" width="100px">
                                </a>
                                <h4><?= $key->name; ?></h4>
                            </div>

                        </td>
                    </tr>
                <?php endforeach; ?>

            </table>
        </div>
        <div class="column">
            <table>
                <tr>
                    <th>Favorites</th>
                </tr>
                <tbody>
                    <?php foreach ($favorites as $key) : ?>
                        <tr>
                            <td>
                                <div>
                                    <h5>Name <?= $key->name; ?></h5>
                                    <h5>Rating <?= $key->rating; ?></h5>
                                    <img src="<?= $key->image_url; ?>" height="100px" width="100px" />
                                </div>

                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>