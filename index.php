<body style="text-align:center">
  <h2>Powerboutique API:</h2>
  <h3>/gesco/autenthication/</h3>
  <?php
  session_start();
  //URL de l'API Powerboutique (Maj 48)
  $API ='https://back.ph1.powerboutique.net/maj_488/api/';

  //Identifiants
  $_GLOBALS['user'] = '';
  $_GLOBALS['profil'] = '';
  $_GLOBALS['pass'] = '';

  // Paramètres de connexion
  $params = http_build_query(array(
    'username' => $_GLOBALS['user'],
    'profil' => $_GLOBALS['profil'],
    'password' => $_GLOBALS['pass']
  ));

  // Header POST
  $header = stream_context_create(array(
    'http' => array(
      'header' => "Content-Type: application/x-www-form-urlencoded\r\n" .
      "Content-Length: " . strlen($params) . "\r\n" ,
      'method' => 'POST',
      'content' => $params
    )
  ));

  //API Gesco/Authentication
  $connexion = get_headers($API.'authentication', 1, $header);

  //Session stockage Authentication

  $_SESSION['Authentication'] = $connexion['Authentication'];

  //Affichage de la clé
  echo '<br><b>' . $_SESSION['Authentication'] . '</b><br>';

  //Définition 'Range' ou non
  function context($range = null)
  {
    $Authentication = $_SESSION['Authentication'];

    //Si 'Range' non défini
    if (!$range) {
      $header = array(
        'http' => array(
          'method' => "GET",
          'header' => "Authentication: $Authentication \r\n"
        )
      );
    }

    //Si 'Range'
    $header = array(
      'http' => array(
        'method' => "GET",
        'header' => "Authentication:$Authentication \r\n" .
        "Range: items=$range\r\n"
      )
    );

    //Transforme en contexte pour envoyer en tant qu'Header
    $header = stream_context_create($header);
    return $header;
  }
  ?>
  <hr>
  <h3>/gesco/client/</h3>

  <?php
  //API Gesco/client
  $clients = file_get_contents($API.'gesco/client', false, context('0-4'));
  $clients = new SimpleXMLElement($clients);
  foreach ($clients->client as $client) {
    echo '<div style="display:inline-block;margin:0 25px  ;">';
    echo '<p><b>Mail</b>:<br>' . $client->adresse->email . '<p>';
    echo '<p><b>Nom:</b><br>' . $client->adresse->nom . '</p>';
    echo '<p><b>Prénom:</b><br>' . $client->adresse->prenom . '</p>';
    echo '</div>';
  }
  ?>

  <hr>
  <h3>/gesco/commande/</h3>

  <?php
  //API Gesco/commande
  $commandes = file_get_contents($API.'gesco/commande', false, context('0-0'));
  $commandes = new SimpleXMLElement($commandes);
  foreach ($commandes->commande as $commande) {
    $id      = $commande->attributes()->id;
    $nom     = $commande->client->adresse->nom;
    $societe = $commande->client->adresse->societe;
    if (strlen($societe) < 1) {
      $societe = $nom;
    }
    $date   = $commande->date;
    $email  = $commande->client->adresse->email;
    $total  = $commande->montants->montant[0];
    $devise = $commande->montants->attributes()->devise;

    //A SUPPRIMER -->
    echo '<div style="display:inline-block;margin:0 25px;">';
    echo '<p style="display:inline-block;"><b>NumCmd:</b><br>' . $id . '<br>';
    echo '<b>Nom Facture:</b><br>' . $nom . '<br>';
    echo '<b>Societe Facuration:</b><br>' . $societe . '<br>';
    echo '<b>Date:</b><br>' . $date . '<br>';
    echo '<b>Email Facuration:</b><br>' . $email . '<br>';
    echo '<b>Total:</b><br>' . $total . ' ' . $devise . '</p>';
    //FIN SUPPRESION <--

    //API Gesco/commande/<ref>/produits
    $produits  = file_get_contents($API.'gesco/commande/' . $id . '/produits', false, context());
    $produits  = new SimpleXMLElement($produits);
    $NbProduit = count($produits->commande->produits->produitCommande);
    echo '<p><b>Nb Produit:</b><br>' . $NbProduit . '</p>';

    //Ligne à ecrire (Format CSV)
    echo '<p><b>' . $id . '</b> CSV  String:</b><br>' . $id . ';' . $nom . ';' . $societe . ';' . $email . ';' . $total . ';' . $NbProduit . ';';

    //Liste produits (Format CSV)
    foreach ($produits->commande->produits->produitCommande as $produit) {
      echo $produit->ref_produit . '|' . $produit->quantite . '|' . $produit->prixUnitaire . '|' . $produit->nom[0] . '$';
    }
    echo '</div><hr>';
  }
