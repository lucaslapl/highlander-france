$.getJSON("./_scripts/hlfr_logs.php", function(logs) {

    // Supprimer les 4 plus anciennes logs
    logs = logs.slice(0, logs.length - 4);

    const logsPerPage = 10;
    let currentPage = 1;

    function renderTable(page) {
        const start = (page - 1) * logsPerPage;
        const end = start + logsPerPage;
        const pageLogs = logs.slice(start, end);

        

        let rows = "";

        pageLogs.forEach((log, index) => {
            const date = new Date(log.date * 1000).toLocaleString("fr-FR", {
                year: "numeric",
                month: "2-digit",
                day: "2-digit",
                hour: "2-digit",
                minute: "2-digit"
            });

            rows += `
                <tr class="log-row" data-index="${index}">
                    <td>${date}</td>
                    <td>${log.map}</td>
                    <td>
                        <a class="log-link" href="https://logs.tf/${log.id}" target="_blank">
                            ${log.title}
                        </a>
                    </td>
                </tr>
            `;
        });

        $("#logsTable tbody").html(rows);

        // Animation progressive
        $(".log-row").each(function(i) {
            setTimeout(() => {
                $(this).addClass("visible");
            }, i * 120); // délai entre chaque ligne (120ms)
        });
    }

    function renderPagination() {
        const totalPages = Math.ceil(logs.length / logsPerPage);
        let buttons = "";

        for (let i = 1; i <= totalPages; i++) {
            buttons += `
                <button class="page-btn ${i === currentPage ? 'active' : ''}" data-page="${i}">
                    ${i}
                </button>
            `;
        }

        $("#pagination").html(buttons);

        $(".page-btn").on("click", function() {
            currentPage = parseInt($(this).data("page"));
            renderTable(currentPage);
            renderPagination();
        });
    }

    // Affichage initial
    renderTable(currentPage);
    renderPagination();
});



$.getJSON("./_scripts/hlfr_indexstats.php", function(stats) {
    $("#matchCount").text(stats.matches);
    $("#hoursPlayed").text(stats.hours);
});



$.getJSON("./_scripts/hlfr_leaderboard.php", function(players) {

    let rows = "";

    players.forEach(p => {
        rows += `
            <tr>
                <td><img src="${p.avatar}" width="48" height="48" style="border-radius:6px"></td>
                <td>
                    <a href="https://steamcommunity.com/profiles/${p.steamid64}" target="_blank">
                        ${p.name}
                    </a>
                </td>
                <td>${p.matches}</td>
            </tr>
        `;
    });

    $("#leaderboardTable tbody").html(rows);
});

