<?php
session_start(); 
include("includes/php/functions.php");
?>

<!DOCTYPE html>
<html>

    <head>
        <meta charset="utf-8" />
        <link rel="stylesheet" href="includes/css/style.css">
        <link rel="icon" type="image/png" href="images/bateau.png">
        <title>Bataille navale | Jeu</title>
    </head>

    <body>
        <header>
            <a href="game.php">
                <img src="images/bateau.png">
                <h1>Bataille navale</h1>
            </a>
        </header>
        <div id="game">

            <?php            
            if (isset($_POST['insc_login']))
                $login = inscription_treatment();
            else if (isset($_POST['connec_login']))
                $login = connection_treatment();
            if (!isset($_SESSION['user']) && $login != 1)                
                user_session_save($login);
            $login = $_SESSION['user']['login'];
            echo "<h2>Bienvenue $login !</h2>";
            if (!isset($_POST['old_match_submit']) && 
                !isset($_POST['new_match_submit']) && 
                !isset($_POST['coord']) && 
                !isset($_POST['reload_submit']))
            {
                matches_noend_list($login);
                users_list($login);
                matches_end_list($login);
            }
            else if (isset($_POST['new_match_submit']))
            {
                new_match($login);
                $_SESSION['reload'] = show_round();
            }
            else if (isset($_POST['old_match_submit']))
            {
                $_SESSION['match']['id'] = $_POST['old_match_id'];
                $_SESSION['reload'] = show_round();
            }
            else if (isset($_POST['reload_submit']))
                $_SESSION['reload'] = show_round();
            if (isset($_POST['coord']))
            {
                if (isset($_POST['coord']) && $_SESSION['reload'] == 0)
                    round_treatment();
                $_SESSION['reload'] = show_round();
            }
            ?>
        </div>
        <footer>
            <?php
            if (isset($_SESSION['user']))
                echo '<form id="deconnexion" method="POST" action="index.php">
                        <input type="submit" name="deconnec_submit" value="Déconnexion">
                        </form>';
            else
                echo '<a href="inscription.php">Inscription</a>';
            ?>
            <a href="#">Règles</a>
            <a href="#">FAQ</a>
        </footer>
    </body>

</html>