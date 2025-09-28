<?php

/**
 * Script pour ajouter des données de test pour la section suggestion
 * À exécuter une seule fois pour tester la fonctionnalité
 */

require_once __DIR__ . "/../lib/pdo.php";

try {
    // Vérifier d'abord s'il y a déjà des trajets en attente
    $check_query = $pdo->prepare("SELECT COUNT(*) as count FROM covoiturage WHERE statut = 2");
    $check_query->execute();
    $existing_count = $check_query->fetch(PDO::FETCH_ASSOC)['count'];

    if ($existing_count > 0) {
        echo "Il y a déjà $existing_count trajet(s) en attente dans la base de données.\n";
        echo "La section suggestion devrait s'afficher normalement.\n";
        exit;
    }

    // Récupérer un utilisateur existant
    $user_query = $pdo->prepare("SELECT user_id FROM user LIMIT 1");
    $user_query->execute();
    $user = $user_query->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "Aucun utilisateur trouvé dans la base de données.\n";
        echo "Veuillez d'abord créer un utilisateur.\n";
        exit;
    }

    // Récupérer une voiture existante
    $voiture_query = $pdo->prepare("SELECT voiture_id FROM voiture LIMIT 1");
    $voiture_query->execute();
    $voiture = $voiture_query->fetch(PDO::FETCH_ASSOC);

    if (!$voiture) {
        echo "Aucune voiture trouvée dans la base de données.\n";
        echo "Veuillez d'abord ajouter une voiture.\n";
        exit;
    }

    // Ajouter quelques trajets en attente pour test
    $trajets_test = [
        [
            'date_depart' => date('Y-m-d', strtotime('+1 day')),
            'heure_depart' => '08:00:00',
            'lieu_depart' => 'Paris',
            'date_arrivee' => date('Y-m-d', strtotime('+1 day')),
            'heure_arrivee' => '10:00:00',
            'lieu_arrivee' => 'Lyon',
            'statut' => 2,
            'nb_place' => 3,
            'prix_personne' => 25.50,
            'duree' => 120
        ],
        [
            'date_depart' => date('Y-m-d', strtotime('+2 days')),
            'heure_depart' => '14:30:00',
            'lieu_depart' => 'Marseille',
            'date_arrivee' => date('Y-m-d', strtotime('+2 days')),
            'heure_arrivee' => '16:30:00',
            'lieu_arrivee' => 'Nice',
            'statut' => 2,
            'nb_place' => 2,
            'prix_personne' => 18.00,
            'duree' => 90
        ],
        [
            'date_depart' => date('Y-m-d', strtotime('+3 days')),
            'heure_depart' => '09:15:00',
            'lieu_depart' => 'Toulouse',
            'date_arrivee' => date('Y-m-d', strtotime('+3 days')),
            'heure_arrivee' => '11:45:00',
            'lieu_arrivee' => 'Bordeaux',
            'statut' => 2,
            'nb_place' => 4,
            'prix_personne' => 22.75,
            'duree' => 150
        ]
    ];

    $insert_query = $pdo->prepare("
        INSERT INTO covoiturage 
        (date_depart, heure_depart, lieu_depart, date_arrivee, heure_arrivee, lieu_arrivee, 
         statut, nb_place, prix_personne, user_id, voiture_id, duree)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $added_count = 0;
    foreach ($trajets_test as $trajet) {
        $insert_query->execute([
            $trajet['date_depart'],
            $trajet['heure_depart'],
            $trajet['lieu_depart'],
            $trajet['date_arrivee'],
            $trajet['heure_arrivee'],
            $trajet['lieu_arrivee'],
            $trajet['statut'],
            $trajet['nb_place'],
            $trajet['prix_personne'],
            $user['user_id'],
            $voiture['voiture_id'],
            $trajet['duree']
        ]);
        $added_count++;
    }

    echo "✅ $added_count trajet(s) en attente ajouté(s) avec succès !\n";
    echo "La section suggestion devrait maintenant s'afficher sur la page trajets.php\n";
} catch (PDOException $e) {
    echo "❌ Erreur lors de l'ajout des données de test : " . $e->getMessage() . "\n";
}
