<?php
/**
 * Bloc « Impact écologique » — nécessite $impactStats (tableau retourné par ImpactEcologiqueService).
 *
 * @var array<string, int|float> $impactStats
 */
if (!isset($impactStats) || !is_array($impactStats)) {
    return;
}
?>
<section class="impact-ecologique py-4 mb-4 rounded-3" aria-labelledby="impact-title">
    <div class="container-fluid px-0">
        <div class="mb-3">
            <h3 id="impact-title" class="h4 fw-bold mb-1">
                <i class="bi bi-tree me-2 text-success"></i>Impact écologique de la plateforme
            </h3>
            <p class="text-muted small mb-0">Indicateurs agrégés sur les trajets terminés et les réservations confirmées.</p>
        </div>
        <div class="row g-3">
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card impact-stat-card h-100 text-center border-0 shadow-sm">
                    <div class="card-body py-3">
                        <i class="bi bi-signpost-split impact-stat-icon text-primary" aria-hidden="true"></i>
                        <div class="impact-stat-value"><?= number_format((float) $impactStats['km_partages'], 0, ',', ' ') ?></div>
                        <div class="impact-stat-label">km partagés</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card impact-stat-card h-100 text-center border-0 shadow-sm">
                    <div class="card-body py-3">
                        <i class="bi bi-tree impact-stat-icon text-success" aria-hidden="true"></i>
                        <div class="impact-stat-value"><?= number_format((float) $impactStats['co2_evite_kg'], 0, ',', ' ') ?></div>
                        <div class="impact-stat-label">kg CO₂ évités</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card impact-stat-card h-100 text-center border-0 shadow-sm">
                    <div class="card-body py-3">
                        <i class="bi bi-check-circle impact-stat-icon text-info" aria-hidden="true"></i>
                        <div class="impact-stat-value"><?= (int) $impactStats['trajets_termines'] ?></div>
                        <div class="impact-stat-label">trajets terminés</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card impact-stat-card h-100 text-center border-0 shadow-sm">
                    <div class="card-body py-3">
                        <i class="bi bi-lightning-charge impact-stat-icon text-warning" aria-hidden="true"></i>
                        <div class="impact-stat-value"><?= (int) $impactStats['trajets_ecologiques'] ?></div>
                        <div class="impact-stat-label">trajets électriques</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card impact-stat-card h-100 text-center border-0 shadow-sm">
                    <div class="card-body py-3">
                        <i class="bi bi-people impact-stat-icon text-secondary" aria-hidden="true"></i>
                        <div class="impact-stat-value"><?= (int) $impactStats['passagers_transportes'] ?></div>
                        <div class="impact-stat-label">passagers transportés</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card impact-stat-card h-100 text-center border-0 shadow-sm">
                    <div class="card-body py-3">
                        <i class="bi bi-person-check impact-stat-icon text-dark" aria-hidden="true"></i>
                        <div class="impact-stat-value"><?= (int) $impactStats['utilisateurs_inscrits'] ?></div>
                        <div class="impact-stat-label">utilisateurs</div>
                    </div>
                </div>
            </div>
        </div>
        <p class="text-muted small mt-3 mb-0">
            CO₂ estimé : trajet solo évité par passager confirmé (0,12 kg/km). Distances calculées via OSRM.
        </p>
    </div>
</section>
