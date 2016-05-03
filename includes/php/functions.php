<?php

/* /!\ DEBUT Sécurité Mots de passe /!\ */
function encryptIt( $q ) {
    $cryptKey  = 'qJB0rGtIn5UB1xG03efyCp';
    $qEncoded      = base64_encode( mcrypt_encrypt( MCRYPT_RIJNDAEL_256, md5( $cryptKey ), $q, MCRYPT_MODE_CBC, md5( md5( $cryptKey ) ) ) );
    return( $qEncoded );
}

function decryptIt( $q ) {
    $cryptKey  = 'qJB0rGtIn5UB1xG03efyCp';
    $qDecoded      = rtrim( mcrypt_decrypt( MCRYPT_RIJNDAEL_256, md5( $cryptKey ), base64_decode( $q ), MCRYPT_MODE_CBC, md5( md5( $cryptKey ) ) ), "\0");
    return( $qDecoded );
}
/* /!\ FIN Sécurité Mots de passe /!\ */

/* /!\ DEBUT Connexion à la DB /!\ */
function DB_connect()
{
    try
    {
        $bdd = new PDO('mysql:host=localhost;dbname=bataille-navale_db', 'root', '', array (PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''));
        $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return ($bdd);
    }
    catch(Exception $e)
    {
        die('Erreur : '.$e->getMessage());
    }
}
/* /!\ FIN Connexion à la DB /!\ */

/* /!\ DEBUT Inscription /!\ */
function inscription_treatment()
{
    $bdd = DB_connect();

    $login = $_POST['insc_login'];
    $password = encryptIt($_POST['insc_password']);
    $firstname = $_POST['insc_firstname'];
    $lastname = $_POST['insc_lastname'];
    $date = date("Y-m-d H:i:s");

    // On vérifie si le login n'existe pas déjà dans la BDD
    $req = $bdd->query('SELECT * FROM users WHERE login = \''.$login.'\'');
    $data = $req->fetch();
    if ($data)
    {
        echo "<h2>Désolé, il y a déjà un utilisateur inscrit à ce nom.</h2>";
        return (1);
    }
    else
    {
        $req = $bdd->prepare('INSERT INTO users(login, password, firstname, lastname, date) VALUES(:login, :password, :firstname, :lastname, :date)');
        $req->execute(array(
            'login' => $login,
            'password' => $password,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'date' => $date
        ));

        return ($login);
    }
}
/* /!\ FIN Inscription /!\ */

/* /!\ DEBUT Connexion /!\ */
function connection_treatment()
{
    $bdd = DB_connect();

    $login = $_POST['connec_login'];
    $password = $_POST['connec_password'];

    $req = $bdd->query('SELECT * FROM users WHERE login = \''.$login.'\'');
    $data = $req->fetch();

    if (!$data) // On vérifie que le login existe dans la BDD
    {
        echo "<h2>Désolé, il n'y a aucun utilisateur inscrit à ce nom.</h2>";
        echo "<a href='index.php'>Réessayer</a>";
        return (1);
    }
    else if (decryptIt($data['password']) != $password) // On vérifie que le mot de passe est bon
    {
        echo "<h2>Désolé, le mot de passe est incorrect.</h2>";
        echo "<a href='index.php'>Réessayer</a>";
        return (1);
    }
    else
        return ($login);
}
/* /!\ FIN Connexion /!\ */

/* /!\ DEBUT Déconnexion /!\ */
function deconnection_treatment()
{
    if (isset($_POST['deconnec_submit']))
    {
        unset($_SESSION['user']);
        unset($_SESSION['match']);
        session_write_close();
    }
}
/* /!\ FIN Déconnexion /!\ */

/* /!\ DEBUT Récupération informations user actuel /!\ */
function user_session_save($login)
{
    $bdd = DB_connect();

    $req = $bdd->query('SELECT * FROM users WHERE login = \''.$login.'\'');
    $data = $req->fetch();

    $_SESSION['user']['id'] = $data['id'];
    $_SESSION['user']['login'] = $login;
    $_SESSION['user']['firstname'] = $data['firstname'];
    $_SESSION['user']['lastname'] = $data['lastname'];
}
/* /!\ FIN Récupération informations user actuel /!\ */

/* /!\ DEBUT Récupération des parties en cours de la BDD /!\ */
function matches_noend_list($login)
{
    echo '<h3>Parties en cours</h3>';
    $bdd = DB_connect();

    $req = $bdd->query('SELECT * FROM matches WHERE end = 0 AND (user1_id = \''.$_SESSION['user']['id'].'\' OR user2_id = \''.$_SESSION['user']['id'].'\')');
    $data = $req->fetch();
    if (!$data)
        echo '<p>Il n\'y a aucune partie en cours.</p>';
    else
    {
        $req = $bdd->query('SELECT * FROM matches WHERE end = 0 AND (user1_id = \''.$_SESSION['user']['id'].'\' OR user2_id = \''.$_SESSION['user']['id'].'\')');
        echo '<form method="POST" action="#">';
        echo '<select name="old_match_id">';
        while ($data = $req->fetch())
        {
            // On détermine si l'ennemi était le user1 ou le user2
            if ($data['user1_id'] == $_SESSION['user']['id'])
                $enemy_id = 'user2_id';
            else
                $enemy_id = 'user1_id';
            // On récupère le nom de l'ennemi dans la table users
            $req_enemy_login = $bdd->query('SELECT login FROM users WHERE id = \''.$data[$enemy_id].'\'');
            $data_enemy_login = $req_enemy_login->fetch();

            //if ($data['login'] != $login)
            echo '<option value="'.$data['id'].'">'.date("d/m/Y", strtotime($data['date'])).' : '.$data_enemy_login['login'].'</option>';
        }
        echo '</select>';
        echo '<input type="submit" name="old_match_submit" value="Reprendre la partie">';
        echo '</form>';
    }
}
/* /!\ FIN Récupération des parties en cours de la BDD /!\ */

/* /!\ DEBUT Récupération des parties terminées de la BDD /!\ */
function matches_end_list($login)
{
    echo '<h3>Parties terminées</h3>';
    $bdd = DB_connect();

    $req = $bdd->query('SELECT * FROM matches WHERE end = 1 AND (user1_id = \''.$_SESSION['user']['id'].'\' OR user2_id = \''.$_SESSION['user']['id'].'\')');
    $data = $req->fetch();
    if (!$data)
        echo '<p>Il n\'y a aucune partie terminée.</p>';
    else
    {
        $req = $bdd->query('SELECT * FROM matches WHERE end = 1 AND (user1_id = \''.$_SESSION['user']['id'].'\' OR user2_id = \''.$_SESSION['user']['id'].'\')');
        echo '<ul>';
        while ($data = $req->fetch())
        {
            // On détermine qui était le user1 et le user2
            if ($data['user1_id'] == $_SESSION['user']['id'])
            {
                $me_id = 'user1_id';
                $me_score = 'user1_score';
                $enemy_id = 'user2_id';
                $enemy_score = 'user2_score';
            }
            else
            {
                $me_id = 'user2_id';
                $me_score = 'user2_score';
                $enemy_id = 'user1_id';
                $enemy_score = 'user1_score';
            }
            // On récupère le nom de l'ennemi dans la table users
            $req_enemy_login = $bdd->query('SELECT login FROM users WHERE id = \''.$data[$enemy_id].'\'');
            $data_enemy_login = $req_enemy_login->fetch();

            if ($data[$me_score] > $data[$enemy_score])
                echo '<li style="color:green">';
            else if ($data[$me_score] < $data[$enemy_score])
                echo '<li style="color:red">';
                else
                echo '<li style="color:blue">';
                echo '<b>'.date("d/m/Y", strtotime($data['date'])).'</b> ⇨ <b>'.$_SESSION['user']['login'].'</b> : '.$data[$me_score].' , <b>'.$data_enemy_login['login'].'</b> : '.$data[$enemy_score].'</li>';
        }
        echo '</ul>';
    }
}
/* /!\ FIN Récupération des parties terminées de la BDD /!\ */

/* /!\ DEBUT Récupération de tous les users de la BDD /!\ */
function users_list($login)
{
    echo '<h3>Nouvelle partie</h3>';
    $bdd = DB_connect();

    $req = $bdd->query('SELECT * FROM users');
    $data = $req->fetch();
    if (!$data)
        echo "<p>Il n'y a pas d'autres joueurs avec qui jouer.</p>";
    else
    {
        $req = $bdd->query('SELECT * FROM users');
        echo '<form method="POST" action="#">';
        echo '<select name="new_match_id">';
        while ($data = $req->fetch())
        {
            if ($data['login'] != $login)
                echo '<option value="'.$data['id'].'">'.$data['login'].'</option>';
        }
        echo '</select>';
        echo '<input type="submit" name="new_match_submit" value="Lancer la partie">';
        echo '</form>';
    }
}
/* /!\ FIN Récupération de tous les users de la BDD /!\ */

/* /!\ DEBUT Transformation de la map en chaîne /!\ */
function map_to_string($map)
{
    $map_str = '';
    for ($i = 'A'; isset($map[$i]); $i++)
    {
        for ($j = 1; isset($map[$i][$j]); $j++)
        {
            $map_str .= $i.$j.'='.$map[$i][$j];
            if ($i != 'F' || $j != 6)
                $map_str .= ',';
        }
    }
    return ($map_str);
}
/* /!\ FIN Transformation de la map en chaîne /!\ */

/* /!\ DEBUT Transformation de la chaîne en map /!\ */
function string_to_map($map_str)
{
    $map_str = explode(',', $map_str);

    $map = [];
    foreach ($map_str as $elt)
        $map[$elt[0]][$elt[1]] = $elt[3];

    return ($map);
}
/* /!\ FIN Transformation de la chaîne en map /!\ */

/* /!\ DEBUT Création d'un round dans la table rounds /!\ */
function new_round($match_id, $actual_user, $user1_score, $user2_score, $map_str, $date)
{
    $bdd = DB_connect();

    $req = $bdd->prepare('INSERT INTO rounds(match_id, actual_user, user1_score, user2_score, map, date) VALUES(:match_id, :actual_user, :user1_score, :user2_score, :map, :date)');
    $req->execute(array(
        'match_id' => $_SESSION['match']['id'],
        'actual_user' => $actual_user,
        'user1_score' => $user1_score,
        'user2_score' => $user2_score,
        'map' => $map_str,
        'date' => $date
    ));
}
/* /!\ FIN Création d'un round dans la table rounds /!\ */

/* /!\ DEBUT Nouvelle partie /!\ */
function new_match($login)
{
    $bdd = DB_connect();

    $user1_id = $_SESSION['user']['id'];
    $user2_id = $_POST['new_match_id'];
    $date = date("Y-m-d H:i:s");

    // On enregistre dans la table matches la nouvelle partie
    $req = $bdd->prepare('INSERT INTO matches(user1_id, user2_id, date) VALUES(:user1_id, :user2_id, :date)');
    $req->execute(array(
        'user1_id' => $user1_id,
        'user2_id' => $user2_id,
        'date' => $date
    ));

    // On récupère de la table matches l'id de la nouvelle partie
    $req = $bdd->query('SELECT id FROM matches WHERE date = \''.$date.'\'');
    $data = $req->fetch();
    $_SESSION['match']['id'] = $data['id'];

    
    // On crée la map
    $map['A'] = [1 => 0, 2 => 0, 3 => 2, 4 => 2, 5 => 2, 6 => 2];
    $map['B'] = [1 => 0, 2 => 2, 3 => 0, 4 => 0, 5 => 0, 6 => 0];
    $map['C'] = [1 => 0, 2 => 2, 3 => 0, 4 => 1, 5 => 1, 6 => 0];
    $map['D'] = [1 => 0, 2 => 2, 3 => 0, 4 => 0, 5 => 0, 6 => 0];
    $map['E'] = [1 => 0, 2 => 2, 3 => 0, 4 => 0, 5 => 1, 6 => 0];
    $map['F'] = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 1, 6 => 0];
    
    /*
    // On crée la map2
    $map['A'] = [1 => 1, 2 => 1, 3 => 0, 4 => 0, 5 => 0, 6 => 0];
    $map['B'] = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0];
    $map['C'] = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0];
    $map['D'] = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0];
    $map['E'] = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0];
    $map['F'] = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0];
    */

    $map_str = map_to_string($map);

    // On crée un premier round dans la table rounds
    new_round($_SESSION['match']['id'], 0, 0, 0, $map_str, $date);
}
/* /!\ FIN Nouvelle partie /!\ */

/* /!\ DEBUT Affichage du round en cours /!\ */
function show_round()
{
    $bdd = DB_connect();

    // On récupère de la table matches les informations sur la partie
    $req_match = $bdd->query('SELECT * FROM matches WHERE id = \''.$_SESSION['match']['id'].'\'');
    $data_match = $req_match->fetch();

    // On récupère de la table rounds tous les rounds de la partie
    $req_round = $bdd->query('SELECT * FROM rounds WHERE match_id = \''.$_SESSION['match']['id'].'\'');
    while($data_round = $req_round->fetch())
        $tab[$data_round['id']] = $data_round;
    // On sélectionne le dernier round en mémoire du match
    $round = $tab[max(array_keys($tab))];

    $_SESSION['match']['previous_user'] = $round['actual_user'];

    // On détermine si l'on est le user1 ou le user2 dans la partie
    if ($data_match['user1_id'] == $_SESSION['user']['id'])
    {
        $_SESSION['match']['user_me'] = 1;
        $_SESSION['match']['user_me_id'] = $data_match['user1_id'];
        $_SESSION['match']['user_me_score'] = $round['user1_score'];
        $_SESSION['match']['user_enemy'] = 2;
        $_SESSION['match']['user_enemy_id'] = $data_match['user2_id'];
        $_SESSION['match']['user_enemy_score'] = $round['user2_score'];
    }
    else
    {
        $_SESSION['match']['user_me'] = 2;
        $_SESSION['match']['user_me_id'] = $data_match['user2_id'];
        $_SESSION['match']['user_me_score'] = $round['user2_score'];
        $_SESSION['match']['user_enemy'] = 1;
        $_SESSION['match']['user_enemy_id'] = $data_match['user1_id'];
        $_SESSION['match']['user_enemy_score'] = $round['user1_score'];
    }

    // Si la partie est finie, on n'affiche pas la map
    if ($data_match['end'])
    {
        echo '<h3>La partie est finie !</h3>';
        if ($_SESSION['match']['user_me_score'] > $_SESSION['match']['user_enemy_score'])
        {
            echo '<h3>Bravo ! Tu as GAGNÉ.</h3>';
        }
        else if ($_SESSION['match']['user_me_score'] < $_SESSION['match']['user_enemy_score'])
        {
            echo '<h3>Dommage ! Tu as PERDU.</h3>';
        }
        else
        {
            echo '<h3>Bravo ! Vous êtes arrivés EX AEQUO.</h3>';
        }
        return (0);
    }

    // On enregistre la map dans le $_SESSION
    $map = string_to_map($round['map']);
    $_SESSION['match']['map'] = $map;

    // On affiche la map à jour
    echo '<table id="map">';
    echo '<tr>';
    for ($j = 0; $j <= 6; $j++)
    {
        if ($j == 0)
            echo '<td></td>';
        else
            echo '<td>'.$j.'</td>';
    }
    echo '</tr>';
    for ($i = 'A'; isset($map[$i]); $i++)
    {
        echo '<tr>';
        for ($j = 0; $j <= 6; $j++)
        {
            if ($j == 0)
                echo '<td>'.$i.'</td>';
            else
            {
                if ($map[$i][$j] == 3)
                    $background_color = 'red';
                else if ($map[$i][$j] == 4)
                    $background_color = 'green';
                    else
                    $background_color = 'white';
                    echo "<td style='background-color:$background_color'></td>";
            }
        }
        echo '</tr>';
    }
    echo '</table>';

    if ($round['actual_user'] == $_SESSION['match']['user_me'] || 
        ($round['actual_user'] == 0 && $_SESSION['match']['user_me'] != 1))
    {
        // On récupère le nom de l'ennemi dans la table users
        $req_enemy_login = $bdd->query('SELECT login FROM users WHERE id = \''.$_SESSION['match']['user_enemy_id'].'\'');
        $data_enemy_login = $req_enemy_login->fetch();

        echo '<h3>C\'est au tour de '.$data_enemy_login['login'].'</h3>';
        echo '<form method="POST" action="game.php">
                    <input type="submit" name="reload_submit" value="↻ Rafraîchir">
                </form>';
        return (1);
    }
    else
    {
        echo '<h3>C\'est à ton tour !</h3>
                <form method="POST" action="#">
                    <br>
                    <input type="text" name="coord" placeholder="Case" required><br><br>
                    <input type="submit" value="Jouer">
                </form>';
        return (0);
    }
}
/* /!\ FIN Affichage du round en cours /!\ */

/* /!\ DEBUT Vérification de la map /!\ */
function verif_map($map)
{
    foreach ($map as $elt)
    {

        if (in_array(1, $elt) || in_array(2, $elt))
            return (1);
    }
    return (0);
}
/* /!\ FIN Vérification de la map /!\ */

/* /!\ DEBUT Traitement du round /!\ */
function round_treatment()
{
    $bdd = DB_connect();
    // On récupère de la table matches les informations sur la partie en cours
    $req_end = $bdd->query('SELECT end FROM matches WHERE id = \''.$_SESSION['match']['id'].'\'');
    $data_end = $req_end->fetch();
    // Si la partie est finie, on n'analyse pas le round
    if ($data_end['end'] == 1)
        return (0);

    // Sinon, on récupère la map de $_SESSION pour la modifier et l'envoyer au nouvel enregistrement dans la table rounds
    $map = $_SESSION['match']['map'];

    if (isset($_POST['coord'][0]) && isset($_POST['coord'][1]) && 
        isset($map[strtoupper($_POST['coord'][0])][$_POST['coord'][1]]))
    {
        if ($map[strtoupper($_POST['coord'][0])][$_POST['coord'][1]] == 0)
        {
            $map[strtoupper($_POST['coord'][0])][$_POST['coord'][1]] = 3;
            echo '<h3>Dommage... C\'est une case vide.</h3>';
        }
        else if ($map[strtoupper($_POST['coord'][0])][$_POST['coord'][1]] == 1 ||
                 $map[strtoupper($_POST['coord'][0])][$_POST['coord'][1]] == 2)
        {
            $map[strtoupper($_POST['coord'][0])][$_POST['coord'][1]] = 4;
            $_SESSION['match']['user_me_score'] += 4;
            echo '<h3>Touché !</h3>';

            // On vérifie s'il y a toujours des cases à toucher dans le bateau
            // S'il n'y en a plus, on enregistre les scores dans la table matches et on termine la partie
            $verif_map = verif_map($map);
            if ($verif_map == 0)
            {
                // On enregistre dans la table matches la nouvelle partie
                $req = $bdd->prepare('UPDATE matches SET user1_score = :user1_score, user2_score = :user2_score, end = :end WHERE id = :id');
                if ($_SESSION['match']['user_me'] == 1)
                {
                    $req->execute(array(
                        'id' => $_SESSION['match']['id'],
                        'user1_score' => $_SESSION['match']['user_me_score'],
                        'user2_score' => $_SESSION['match']['user_enemy_score'],
                        'end' => 1
                    ));
                }
                else
                {
                    $req->execute(array(
                        'id' => $_SESSION['match']['id'],
                        'user1_score' => $_SESSION['match']['user_enemy_score'],
                        'user2_score' => $_SESSION['match']['user_me_score'],
                        'end' => 1
                    ));
                }
            }
        }
        else
            echo '<h3>Cette case avait déjà été modifiée. Tu passes ton tour.</h3>';

        // On transforme la map en chaîne
        $map_str = map_to_string($map);

        // On crée un nouveau round dans la table rounds
        if ($_SESSION['match']['user_me'] == 1)
            new_round($_SESSION['match']['id'], $_SESSION['match']['user_me'], $_SESSION['match']['user_me_score'], 
                      $_SESSION['match']['user_enemy_score'], $map_str, date("Y-m-d H:i:s"));
        else
            new_round($_SESSION['match']['id'], $_SESSION['match']['user_me'], $_SESSION['match']['user_enemy_score'], 
                      $_SESSION['match']['user_me_score'], $map_str, date("Y-m-d H:i:s"));
    }
    else
        echo '<h3>Désolé, '.$_POST['coord'].' ne sont pas des coordonnées.</h3>';
}
/* /!\ FIN Traitement du round /!\ */

?>