<?php
// 1. Récupérer les matchs à venir (date supérieure ou égale à "maintenant")
// On les classe du plus proche au plus lointain (ASC) et on limite à 5 pour ne pas surcharger l'accueil
$currentTime = time();
$stmtAgenda = $db->prepare("
    SELECT * FROM etf2l_matches 
    WHERE match_date >= :current_time 
    ORDER BY match_date ASC 
    LIMIT 5
");
$stmtAgenda->execute([':current_time' => $currentTime]);
$prochainsMatchs = $stmtAgenda->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="etf2l-agenda-container">
    <div class="agenda-header flex space-between align-center">
        <h3><i class="fa-solid fa-calendar-days"></i> Matchs Équipes FR (ETF2L)</h3>
        <span class="badge-live-info">Prochains matchs</span>
    </div>

    <?php if (empty($prochainsMatchs)): ?>
        <div class="agenda-empty">
            <p><i class="fa-solid fa-circle-info"></i> Aucun match de prévu pour le moment.</p>
        </div>
    <?php else: ?>
        <div class="agenda-list">
            <?php foreach ($prochainsMatchs as $match): ?>
                <?php 
                $dt = new DateTime("@{$match['match_date']}");
                $dt->setTimezone(new DateTimeZone('Europe/Paris'));
                $dateMatch = $dt->format('d/m');
                $heureMatch = $dt->format('H:i');
                ?>
                <div class="agenda-item flex align-center">
                    
                    <div class="match-date-box text-center">
                        <span class="match-date"><?= $dateMatch ?></span>
                        <span class="match-hour"><?= $heureMatch ?></span>
                    </div>

                    <div class="match-details flex-1">
                        <div class="competition-title"><?= htmlspecialchars($match['competition_name']) ?></div>
                        <div class="teams-line flex align-center">
                            
                            <span class="team-name text-right flex align-center justify-end gap-10">
                                <?php 
                                $flag1 = ($match['team1_country'] === 'france') ? 'fr' : 'eu'; 
                                ?>
                                <img src="/_img/flags/<?= $flag1 ?>.gif" alt="<?= $flag1 ?>" class="team-flag" title="<?= ucfirst($match['team1_country']) ?>">
                                <span class="truncate-text"><?= htmlspecialchars($match['team1_name']) ?></span>
                            </span>
                            
                            <span class="vs-separator">VS</span>
                            
                            <span class="team-name text-left flex align-center gap-10">
                                <?php 
                                $flag2 = ($match['team2_country'] === 'france') ? 'fr' : 'eu'; 
                                ?>
                                <span class="truncate-text"><?= htmlspecialchars($match['team2_name']) ?></span>
                                <img src="/_img/flags/<?= $flag2 ?>.gif" alt="<?= $flag2 ?>" class="team-flag" title="<?= ucfirst($match['team2_country']) ?>">
                            </span>

                        </div>
                    </div>

                    <div class="match-action">
                        <a href="https://etf2l.org/matches/<?= $match['match_id'] ?>" target="_blank" class="btn-match-link" title="Voir sur ETF2L">
                            <i class="fa-solid fa-chevron-right"></i>
                        </a>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>