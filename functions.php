<?php
function connexion() {
    include "connex.php";
    $strConnex="host=$dbHost dbname=$dbName user=$dbUser password=$dbPassword";
    $ptrDB = pg_connect($strConnex);
    return $ptrDB;
}
function valideForm(&$method, $tabCles) {
    foreach ($tabCles as $cle) {
        if (!isset($method[$cle]))
            return FALSE;
        if (!$method[$cle])
            return FALSE;
        }
	return TRUE;
}
/**
 * getRowByID
 * @param string $table
 *  @param int $id
 * @return array tableau associatif représentant un enregistrement d'une table passé en paramètre
 * DONE Brice
 */
function getRowByID($table, $id) {
    $ptrDB = connexion();
    $i = substr($table, 0, 3);
    $i .= "_id";
    $query = "SELECT * FROM $table WHERE $i = $1";

    $result = pg_prepare($ptrDB, "reqprep", $query);
    $ptrQuery = pg_execute($ptrDB, "reqprep", array($id));

    if (isset($ptrQuery))
        $resu = pg_fetch_assoc($ptrQuery);

        if (empty($resu))
            $resu =  array("message" => "Identifiant non valide : $id");

    pg_free_result($ptrQuery);
    pg_close($ptrDB);

    return $resu;
  }
  /**
   * deleteRowByID
   * supprime un enregistrement d'une table passé en paramètre
   * @param string $table
  *  @param int $id
   * @return boolean si oui ou non la supression à marché
   * DONE Brice
   */
function deleteRowByID($table, $id) {
    $ptrDB = connexion();
    $i = substr($table, 0, 3);
    $i .= "_id";

    $query1 = "DELETE FROM participe WHERE $i = $1";
    $query2 = "DELETE FROM $table WHERE $i = $1";
    pg_prepare($ptrDB, "reqprep1", $query1);
    pg_prepare($ptrDB, "reqprep2", $query2);
    $ptrQuery1 = pg_execute($ptrDB, "reqprep1", array($id));
    $ptrQuery2 = pg_execute($ptrDB, "reqprep2", array($id));
    if ($ptrQuery2 === false){return false;}
    else {pg_free_result($ptrQuery1); pg_free_result($ptrQuery2);return true;}
}

  /**
   * createPost
   * Permet de générer un poste artiste ou album
   * @param array $tab
   * @return void
   * DONE Tahina TODO BUG afficher les participants des albums
   */
  function createPost(array $tab, $type) {
    if ($type == "artiste" ){
      $ptrDB = connexion();
      $query1 = "SELECT alb_titre FROM album WHERE alb_id IN
                 (SELECT alb_id FROM participe WHERE art_id = $1)"; // requete récupère les albums dans lequelle à participé l'artiste
      $query2 = "SELECT DISTINCT instrument FROM participe WHERE art_id = $1"; // requete récupère les instruments

      pg_prepare($ptrDB,"query1",$query1);
      pg_prepare($ptrDB,"query2",$query2);
      $ptrQuery = pg_execute($ptrDB, "query1", array($tab['art_id']));

      if (isset($ptrQuery)){
        $albums = array();
        while($ligne = pg_fetch_row($ptrQuery)) {
          foreach ($ligne as $elm) {
            array_push($albums, $elm) ;
          }
        }
      }

        $ptrQuery = pg_execute($ptrDB, "query2", array($tab['art_id']));
        if (isset($ptrQuery)){
          $instrument ="";
          while($ligne = pg_fetch_row($ptrQuery)) {
            foreach ($ligne as $elm) {
              $instrument .= "".$elm;
            }
          }
        }

      //-------------------------
      $alb_list = "<ul>";
      foreach ($albums as $v) {
        $alb_list .= "<li>".$v."</li>";
      }
      $alb_list .="</ul>";
      //------------------------
      $tab['album'] =$albums;
      $tab['instrument'] = $instrument;
      $tab['description'] ="Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.";

      $statut =(is_null($tab['art_datemort']))? "vivant":" Mort (".$tab['art_datemort'].")";
      echo '<div class="container" >
            <div class="cadre">
            ';

               createButton("update",$type,$tab);
               createButton("delete",$type,$tab);
               echo  '<div class="post">
                <table>';

                echo '
                          <th><h1>'.$tab['art_prenom'].' '.$tab['art_nom'].'</h1></th>
                          <tr><td><b>Pseudo :</b> '.$tab['art_pseudo'].'</tr>
                          <tr><td><b>Date de naissance :</b> '.$tab['art_datenaissance'].'</tr>
                          <tr><td><b>Statut :</b> '.$statut.'</td></tr>
                          <tr><td><b>Instrument :</b>'.$tab['instrument'].'</td></tr>
                          <tr><td><b>Album.s :</b>'.$alb_list.'</td></tr>
                          <tr><td><p><b>Description : </b>'.$tab['description'].'</p></td></tr>
                          ';

    }
    else if ($type == "album"){
      $query = "SELECT art_nom FROM artiste WHERE art_id IN
                 (SELECT art_id FROM participe WHERE alb_id = $1)";
      pg_prepare(connexion(),"query",$query);
      $ptrQuery = pg_execute(connexion(), "query", array($tab['alb_id']));

      if (isset($ptrQuery)){
        $artistes ="<ul>";
        while($ligne = pg_fetch_row($ptrQuery)) {
          foreach ($ligne as $elm) {
            $artistes .= "<li>".$elm."</li>";
          }
        }
        $artistes.="</ul>";
      }



      $tab['description'] ="Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.";

      echo '<div class="container" >
            <div class="cadre">

            ';

            createButton("update",$type,$tab);
            createButton("delete",$type,$tab);
              echo  '<div class="post">
                <table>';

            echo'
                      <th><h1>'.$tab['alb_titre'].'</h1></th>
                      <tr><td><b>Sortie :</b>'.$tab['alb_sortie'].'</tr>
                      <tr><td><b>Durée :</b>'.$tab['alb_duree'].'</tr>
                      <tr><td><b>Producteur :</b>'.$tab['alb_producteur'].'</td></tr>
                      <tr><td><b>Label :</b>'.$tab['alb_label'].'</td></tr>
                      <tr><td><b>Genre :</b>'.$tab['alb_genre'].'</td></tr>
                        <tr><td><b>Participants :</b>'.$artistes.'</td></tr>
                      <tr><td><p><b>Description : </b>'.$tab['description'].'</p></td></tr>';
    }
    echo '    </table>
              <br/><img src="img/noImg.png">

            </div>
          </div>
      </div>';
  }

    /**
     * insertRow
     * Permet d'insérer un nouvelle enregistrement dans une table choisit en paramètre
     * @param string $table
     * @param array $row
     * @return boolean si oui ou non l'insertion à fonctionnée
     * DONE Abdelaziz / Tahina
     */

     function insertRow($table,$row){
         if($table == "artiste"){
           foreach ($row as $key => $value) {
             if (!is_array($value)) {
               if($value === '') $row["$key"] = "NULL";
               else { $row["$key"] = "'".$value."'";}
             }
             else {
               $value = implode($value);
               if($value === '') $row["$key"] = "NULL";
               else { $row["$key"] = "'".$value."'";}
             }
           }
           $str ="(".$row['art_pseudo'].",".$row['art_nom'].",".$row['art_prenom'].",".$row['art_datenaissance'].",".$row['art_datemort'].")";
           $query = "INSERT INTO artiste (art_pseudo, art_nom, art_prenom, art_datenaissance, art_datemort) VALUES ".$str;
           $ptrDB = connexion();
           $ptrQuery = pg_query($ptrDB, $query);
           // PARTICIPE A FAIRE -----
           $i = 0;
           $query1 = "SELECT last_value FROM artiste_art_id_seq";
           $ptrQuery1 = pg_query($ptrDB,$query1);
           $art_id = implode(pg_fetch_row($ptrQuery1));

           foreach ($_GET['album'] as $key => $value) {
             $query = "SELECT alb_id FROM album where alb_titre = $1";
             pg_prepare($ptrDB,"prep".$i,$query);
             $ptrQuery = pg_execute($ptrDB,"prep".$i++, array("$value"));

             if (isset($ptrQuery)){
               while ($row = pg_fetch_row($ptrQuery)) {
                 foreach ($row as $val) {
                    $ins  = "'".$_GET['instrument']."'";
                   $query10 = "INSERT into participe (art_id,alb_id,instrument) VALUES"."($art_id,$val,$ins)";
                   $ptrQuery10 = pg_query($ptrDB,$query10);
                 }
               }
             }
           }

           if($ptrQuery10 === false)  return false;
           else return true;
         }

         else if( $table== "album"){
           foreach ($row as $key => $value) {
             if (!is_array($value)) {
               if($value === '') $row["$key"] = "NULL";
               else { $row["$key"] = "'".$value."'";}
             }
             else {
               $value = implode(",",$value);
               if($value === '') $row["$key"] = "NULL";
               else { $row["$key"] = "'".$value."'";}
             }
           }
           $str ="(".$row['alb_titre'].",".$row['alb_sortie'].",".$row['alb_duree'].",".$row['alb_genre'].",".$row['alb_producteur'].",".$row['alb_label'].")";
           $query = "INSERT INTO album (alb_titre, alb_sortie, alb_duree, alb_genre, alb_producteur,  alb_label) VALUES ".$str;
           $ptrQuery = pg_query(connexion(), $query);
           if($ptrQuery === false )  return false;
           else return true;
         }
         return false;
       }
    /**
     * updateRow
     * Permet de mettre à jour un enregistrement dans une table choisit en paramètre
     * @param string $table
     * @param array $row
     * @return boolean si oui ou non la mise à jour à fonctionnée
     * TODO Abdelaziz BUG ne pas update l'id PLEASE !!!!!
     */

     function updateRow($table, $row)  {
         $ptrDB = connexion();
       if ($table == "artiste") {
         $str = "UPDATE artiste SET art_nom = '".$row['art_nom']."'";
         foreach ($row as $key => $value) {
           if (!is_array($value) && $key !="art_nom" && $key !="instrument" && $key !="description" && $key !="art_id" && $key !="enregistrement") {
             if($value === '') $row["$key"] = "$key =  NULL";
             else { $row["$key"] = "$key = '".$value."'";}
             $str .=", ".$row["$key"];
           }
         }
         $str .= " WHERE art_id = ".$row['art_id'];
         $ptrQuery = pg_query($ptrDB,$str);
         //------- PARTICIPE -----------------
         $deleteQuery = "DELETE FROM participe WHERE art_id = ".$row['art_id'];
         $deletePtrQuery = pg_query($ptrDB,$deleteQuery);

         if (isset($_GET['album'])){
           foreach ($_GET['album'] as $alb) {
             $insertQuery = "INSERT INTO participe (art_id, alb_id, instrument) VALUES";
             $sou = "(select alb_id from album where alb_titre='$alb')";
             $insertQuery .= "(".$row['art_id'].",$sou,'".$row['instrument']."')";
             pg_query($ptrDB, $insertQuery);
           }
         }
         //-----------------------------------
         if($ptrQuery === false )  return false;
         else return true;

       }
       else if ($table == "album") {
         $str = "UPDATE album SET alb_titre = '".$row['alb_titre']."'";
         foreach ($row as $key => $value) {
           if (!is_array($value) && $key !="alb_titre"  && $key !="description" && $key !="alb_id" && $key !="enregistrement") {
             if($value === '') $row["$key"] = "$key =  NULL";
             else { $row["$key"] = "$key = '".$value."'";}
             $str .=", ".$row["$key"];
           }
         }
         $str .= " WHERE alb_id = ".$row['alb_id'];
         $ptrQuery = pg_query($ptrDB,$str);
         if($ptrQuery === false )  return false;
         else return true;
       }
       return false;
    }
     /**
      * createButton
      * Permet de créer un bouton de type supprimer ou modifier pour une catégory (artiste ou album) pour éviter la répétition de code
      * @param string $type
      * @param string $category
      * DONE Tahina
      */

      function createButton($type,$category,$table){
        $param = "";
        foreach ($table as $key => $value) {
            if (is_array($table["$key"])){
              $string = "";
              foreach ($table["$key"] as $val) {
                $string .= ",".$val;
              }
            }
            else $string = $table["$key"];
            $param .= ''.$key.'='.$string.'&amp;';
         }

        if ($type =="delete"){
                echo '<a href="delete.html.php?'.$param.'"';
          echo '
          <button class ="button">Supprimer</button></a>

          ';
        }
        else if ($type =="update"){


          echo '<a href="form_'.$category.'.html.php?'.$param.'"';

          echo '<button class="button">Modifier</button></a>';


        }
      }

 ?>
