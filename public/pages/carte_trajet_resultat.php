<?php
/**
 * Carte trajet (recherche ou filtres) — utilisée par trajets.php.
 *
 * Variables attendues : $covoiturage (array), $etapes_by_covoiturage (array),
 * $col_class (string classes colonne), $card_extra_class (string optionnelle pour la carte).
 * Optionnel : $trajets_retour_query (array) — paramètres pour préserver la recherche sur la page détail.
 */
if (!isset($covoiturage, $etapes_by_covoiturage, $col_class)) {
    return;
}
$card_extra_class = isset($card_extra_class) ? trim((string)$card_extra_class) : '';
$detail_url_params = array_merge(
    ['id' => (int)$covoiturage['covoiturage_id']],
    isset($trajets_retour_query) && is_array($trajets_retour_query) ? $trajets_retour_query : []
);
$detail_href = 'detail_covoiturage.php?' . http_build_query($detail_url_params);
?>
<div class="<?= htmlspecialchars($col_class, ENT_QUOTES, 'UTF-8') ?>" role="listitem">
    <div class="card h-100 border shadow-sm w-100<?= $card_extra_class !== '' ? ' ' . htmlspecialchars($card_extra_class, ENT_QUOTES, 'UTF-8') : '' ?>">
        <div class="card-header bg-dark text-white text-center border-light py-2">
            <h6 class="mb-0 small"><i class="bi bi-car-front me-1"></i>Trajet disponible</h6>
        </div>
        <div class="card-body p-3 d-flex flex-column">
            <p class="mb-2 small"><strong><?= htmlspecialchars($covoiturage['lieu_depart']) ?></strong> → <strong><?= htmlspecialchars($covoiturage['lieu_arrivee']) ?></strong></p>
            <p class="mb-2 small text-muted">
                <i class="bi bi-calendar-event me-1"></i><?= date('d/m/Y', strtotime($covoiturage['date_depart'])) ?> à <?= date('H:i', strtotime($covoiturage['heure_depart'])) ?>
            </p>
            <p class="mb-2 small"><i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($covoiturage['prenom'] . ' ' . $covoiturage['nom']) ?></p>

            <?php
            $etapes_trajet = $etapes_by_covoiturage[(int)$covoiturage['covoiturage_id']] ?? [];
            $depart_nom = strtolower(trim((string)$covoiturage['lieu_depart']));
            $arrivee_nom = strtolower(trim((string)$covoiturage['lieu_arrivee']));
            $etapes_intermediaires = array_values(array_filter($etapes_trajet, static function ($nom) use ($depart_nom, $arrivee_nom) {
                $nom_normalise = strtolower(trim((string)$nom));
                return $nom_normalise !== '' && $nom_normalise !== $depart_nom && $nom_normalise !== $arrivee_nom;
            }));
            $etapes_a_afficher = !empty($etapes_intermediaires)
                ? $etapes_intermediaires
                : $etapes_trajet;
            ?>
            <?php if (!empty($etapes_a_afficher)): ?>
                <div class="filter-results-etapes mb-2">
                    <h6 class="text-primary mb-1 small">
                        <i class="bi bi-signpost-2 me-1"></i>Etape(s) programmée(s)
                    </h6>
                    <p class="mb-0 small text-muted"><?= htmlspecialchars(implode(' → ', $etapes_a_afficher)) ?></p>
                </div>
            <?php endif; ?>

            <div class="d-flex flex-wrap justify-content-center gap-2 mt-2">
                <?php
                $nb_places = $covoiturage['nb_place'];
                $badge_class = 'badge-places badge-places--red';
                if ($nb_places >= 3) {
                    $badge_class = 'badge-places badge-places--green';
                } elseif ($nb_places == 2) {
                    $badge_class = 'badge-places badge-places--orange';
                }
                ?>
                <span class="badge <?= $badge_class ?>"><i class="bi bi-people me-1"></i><?= $nb_places ?> place<?= $nb_places > 1 ? 's' : '' ?></span>
                <span class="badge bg-warning text-dark"><i class="bi bi-coin me-1"></i><?= number_format($covoiturage['prix_personne'], 0) ?> crédits</span>
            </div>
        </div>
        <div class="card-footer text-center">
            <a href="<?= htmlspecialchars($detail_href, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary btn-sm">
                <i class="bi bi-eye me-1"></i>Voir le détail
            </a>
        </div>
    </div>
</div>
