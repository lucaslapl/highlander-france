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
                <td>${log.id}</td>
                <td>
                    <a href="https://logs.tf/${log.id}" target="_blank">
                        ${log.title}
                    </a>
                </td>
                <td>${date}</td>
                <td>${log.map}</td>
            </tr>
        `;
    });

    $("#logsTable tbody").html(rows);
});

$.getJSON("./_scripts/get_staff.php", function(players) {

    let html = "";

    players.forEach(p => {
        html += `
            <div class="staff-member">
                <img src="${p.avatarfull}" alt="${p.personaname}" class="staff-avatar">
                <h3>${p.personaname}</h3>
                <a href="${p.profileurl}" target="_blank">Profil Steam</a>
            </div>
        `;
    });

    $("#staff").html(html);
});
