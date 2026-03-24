$.getJSON("./_scripts/hlfr_logs.php", function(logs) {

    let rows = "";

    logs.forEach(log => {
        const date = new Date(log.date * 1000).toLocaleString("fr-FR", {
            year: "numeric",
            month: "2-digit",
            day: "2-digit",
            hour: "2-digit",
            minute: "2-digit"
        });

        rows += `
            <tr>
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
});

$.getJSON("./_scripts/hlfr_indexstats.php", function(stats) {
    $("#matchCount").text(stats.matches);
    $("#hoursPlayed").text(stats.hours);
});
