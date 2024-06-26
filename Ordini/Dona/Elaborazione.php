<?php
session_start();
$a=$_COOKIE['PHPSESSID'];

// Percorso al file di configurazione
$configFilePath = '../../dir_queries/queries.ini';

// Caricamento delle configurazioni
$queryConfig = parse_ini_file($configFilePath, true);
               
  $db = getenv('PG_DATABASE');
$dbconn = pg_connect($db);

  $resultRemember = pg_query_params($dbconn, $queryConfig['database_queries']['fetch_user_data'], array($a));
  $tuple = pg_fetch_array($resultRemember, null, PGSQL_ASSOC);
  
  if ($tuple) {
  
  
  $_SESSION['cognome'] = $tuple["cognome"];
  $_SESSION['email'] = $tuple["email"];
  $_SESSION['password'] = $tuple["pswd"];
  $_SESSION['cap'] = $tuple["cap"];
  $_SESSION['cellulare'] = $tuple["cellulare"];
  $_SESSION['cf'] = $tuple["cf"];
  $_SESSION['città'] = $tuple["città"];
  $_SESSION['via'] = $tuple["via"];
  $_SESSION['regione'] = $tuple["regione"];
  $_SESSION['codice'] = $tuple["codcliente"];

  $_SESSION['loggato'] = 1;
  $_SESSION['nome'] = $tuple["nome"];
  }
//Se questa pagina viene lanciata manualmente, senza che ci siano Transazioni da elaborare o arrivandoci tramite link...vengo ributtato nella Home.
if ((!isset($_SESSION["loggato"])) && (!isset($_COOKIE["nome"])) || !isset($_GET['dati'])  ) {
    header("location: ../../Home_Section/index.php");
    return;
}
if (isset($_SESSION["codice"])){
    $loggato = $_SESSION["codice"];
}

if (isset($_COOKIE["codice"])){
    $loggato = $_COOKIE["codice"];
}
$db = getenv('PG_DATABASE');
$dbconn = pg_connect($db);


//Ci prendiamo i dati dei prodotti inseriti nel carrello dall'utente mediante una chiamata AJAX col GET dal JS, e mettiamo i dati dentro un array.
$arr = explode(",", $_GET['dati']); 

$result = pg_query_params($dbconn, $queryConfig['database_queries']['product_code_by_name'], array($arr[0]));



for ($i = 0; $i < count($arr) - 2; $i++) {
    $str = $arr[$i];
    $arr2 = (explode("|", $str)); //Riformattiamo i dati in base a come li abbiamo costruiti nel JS (riferimento riga 70 di "DinamicTable.js")
    $dataCon = $arr[count($arr) - 1]; 
    $indirizzo = $arr[count($arr) - 2]; 

    /*Facciamo (prima dell'inserimento della transazione), un ulteriore query, verifichiamo se ci sono dei prodotti NUOVI (non presenti nel DB a livello di Nome/Tipologia), inseriti
    dall'utente.*/
    
    $result = pg_query_params($dbconn, $queryConfig['database_queries']['check_product_exists'], array($arr2[0]));

    //Se il prodotto è già presente nel DB semplicemente ne prendo il codice/ID/PK di riferimento.
    if ($codiceProdottoArr = pg_fetch_array($result, null, PGSQL_ASSOC)) {
        foreach ($codiceProdottoArr as $key => $value) {
            $codiceProdotto = $value;
            break;
        }
    } else { //Altrimenti lo inserisco appositamente con tutti i JOIN e le FK del caso.
        $NomeProdNuovo = $arr2[0];
        $TipProdNuovo = $arr2[1];
       
        $result2 = pg_query_params($dbconn,  $queryConfig['database_queries']['insert_new_product'], array($NomeProdNuovo, $TipProdNuovo));
        if (!$result2) {
            die("c'è stato un errore");
        }
        
        $result2 = pg_query_params($dbconn, $queryConfig['database_queries']['product_code_by_name'], array($NomeProdNuovo));
        if ($codiceProdottoArr = pg_fetch_array($result2, null, PGSQL_ASSOC)) {
            foreach ($codiceProdottoArr as $key => $value) {
                $codiceProdotto = $value;
                break;
            }
        }
    }
    $ScadProd = $arr2[2];

    $quantita = $arr2[3];

    $tag=$arr2[count($arr2) - 1];

    //Inserisco nel DB i dati inerenti alla transazione del prodotto (cosa iterata per ogni prodotto del carrello):
    $Inserimento = pg_query_params($dbconn, $queryConfig['database_queries']['insert_transaction'], array(date("Y-m-d"), $loggato, $codiceProdotto, $quantita, $ScadProd, $indirizzo, $dataCon, $tag));
}



if ($result) {
    $_SESSION["success"]=1;
    echo "<script>
        var src='../../Home_Section/index.php';
        window.location.href=src;
    </script>";
} else die("c'è stato un errore");
?>