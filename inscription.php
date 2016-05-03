<?php session_start(); ?>

<!DOCTYPE html>
<html>

    <head>
        <meta charset="utf-8" />
        <link rel="stylesheet" href="includes/css/style.css">
        <link rel="icon" type="image/png" href="images/bateau.png">
        <title>Bataille navale | Inscription</title>
    </head>

    <body>
        <header>
            <img src="images/bateau.png">
            <h1>Bataille navale</h1>
        </header>
        <div id="inscription">
            <h2>INSCRIPTION</h2>
            <?php
            if (isset($_SESSION['user']))
            {
                echo '<h3>Tu es déjà connecté sous le nom de '.$_SESSION['user']['login'].'</h3>';
                echo '<a href="game.php"><h3>Retourner au jeu</h3></a>';
            }
            else
            {
                echo '<form method="POST" action="game.php">
                            <input type="text" name="insc_login" placeholder="Pseudo" required><br><br>
                            <input type="password" name="insc_password" placeholder="Mot de passe" required><br><br>
                            <input type="text" name="insc_firstname" placeholder="Prénom" required><br><br>
                            <input type="text" name="insc_lastname" placeholder="Nom" required><br><br>
                            <input type="submit" name="insc_submit" value="Valider">
                        </form>';
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