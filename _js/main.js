$.getJSON("./_scripts/hlfr_logs.php", function(logs) {

    // Supprimer les 4 plus anciennes logs
    logs = logs.slice(0, logs.length - 4);

    const logsPerPage = 10;
    let currentPage = 1;

    let filteredLogs = [...logs];

    function applyFilters() {
        const dateFilter = $("#filter-date").val().trim().toLowerCase();
        const mapFilter = $("#filter-map").val().trim().toLowerCase();
        const titleFilter = $("#filter-title").val().trim().toLowerCase();

        filteredLogs = logs.filter(log => {
            const dateStr = new Date(log.date * 1000).toLocaleString("fr-FR", {
                year: "numeric",
                month: "2-digit",
                day: "2-digit",
                hour: "2-digit",
                minute: "2-digit"
            }).toLowerCase();

            const mapStr = log.map.toLowerCase();
            const titleStr = log.title.toLowerCase();

            if (dateFilter && !dateStr.includes(dateFilter)) return false;
            if (mapFilter && !mapStr.includes(mapFilter)) return false;
            if (titleFilter && !titleStr.includes(titleFilter)) return false;

            return true;
        });

        currentPage = 1;
        renderTable(currentPage);
        renderPagination();
    }

    function renderTable(page) {
        const start = (page - 1) * logsPerPage;
        const end = start + logsPerPage;
        const pageLogs = filteredLogs.slice(start, end);

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

        $(".log-row").each(function(i) {
            setTimeout(() => $(this).addClass("visible"), i * 80);
        });
    }

    function renderPagination() {
        const totalPages = Math.ceil(filteredLogs.length / logsPerPage);
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

    // Événements des filtres
    $("#filter-date").on("input", applyFilters);
    $("#filter-map").on("input", applyFilters);
    $("#filter-title").on("input", applyFilters);
    

    // Affichage initial
    applyFilters();
});



$.getJSON("./_scripts/hlfr_indexstats.php", function(stats) {
    $("#matchCount").text(stats.matches);
    $("#hoursPlayed").text(stats.hours);
});