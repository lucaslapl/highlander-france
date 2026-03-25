<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Highlander France</title>
    <link rel="stylesheet" href="_css/main.css">
</head>
<body>

    <main id="main">

        <?php include("_inc/header.php"); ?>

        <?php include("_inc/nav.php"); ?>


        <section id="content">
            <h3>Stats des Matchs</h3>
            <p>Consultez les logs détaillés des matchs de Highlander France.</p>

            <table id="logsTable" border="0" cellspacing="20">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Carte</th>
                        <th>Titre</th> 
                    </tr>
                </thead>
                <tbody id="logs">
                    
                </tbody>
            </table>
        </section>

    </main>

    <?php include("_inc/footer.php"); ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://kit.fontawesome.com/2f306d349c.js" crossorigin="anonymous"></script>
<script src="_js/main.js"></script>
</body>
</html>