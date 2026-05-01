<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Highlander France</title>
    <link rel="stylesheet" href="_css/main.css">
</head>
<body>

    

    <?php include("_inc/header.php"); ?>

    <main id="main">
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

            <div id="pagination" class="pagination"></div>

        </section>

    </main>

    <?php include("_inc/footer.php"); ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://kit.fontawesome.com/2f306d349c.js" crossorigin="anonymous"></script>
<script src="_js/main.js"></script>
<script>
    window.addEventListener("load", function () {

    const content = document.querySelector("#content");
    const offset = -92; // ajuste comme tu veux

    if (!content) return;

    // Attendre 1 seconde avant de démarrer l'animation
    setTimeout(() => {

        const target = content.getBoundingClientRect().top + window.scrollY + offset;
        const duration = 1000; // durée de l'animation
        const start = window.scrollY;
        const distance = target - start;
        const startTime = performance.now();

        function easeOutQuad(t) {
            return t * (2 - t);
        }

        function animateScroll(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const eased = easeOutQuad(progress);

            window.scrollTo(0, start + distance * eased);

            if (progress < 1) {
                requestAnimationFrame(animateScroll);
            }
        }

        requestAnimationFrame(animateScroll);

    }, 300);
});

</script>
</body>
</html>