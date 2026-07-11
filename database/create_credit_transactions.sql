-- Historique des mouvements de crédits (Phase 2 — cœur métier)
CREATE TABLE IF NOT EXISTS `credit_transactions` (
    `transaction_id` int NOT NULL AUTO_INCREMENT,
    `user_id` int NOT NULL,
    `montant` int NOT NULL COMMENT 'Positif = crédit, négatif = débit',
    `solde_apres` int NOT NULL,
    `type_operation` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
    `reference_id` int DEFAULT NULL COMMENT 'ID réservation, covoiturage, etc.',
    `description` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
    `date_transaction` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`transaction_id`),
    KEY `idx_credit_user` (`user_id`),
    KEY `idx_credit_type` (`type_operation`),
    CONSTRAINT `credit_transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;
