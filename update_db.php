<?php

try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=gestion_loyers_symfony;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Ajout de la colonne loyer_de_base si elle n'existe pas
    $result = $pdo->query("SHOW COLUMNS FROM bien LIKE 'loyer_de_base'")->fetch();
    if (!$result) {
        $pdo->exec("ALTER TABLE bien ADD loyer_de_base INT DEFAULT NULL");
        echo "✅ Colonne 'loyer_de_base' ajoutée avec succès à la table 'bien'.\n";
    } else {
        echo "ℹ️ La colonne 'loyer_de_base' existe déjà.\n";
    }

    // 2. Initialisation des valeurs par défaut pour les tests
    $pdo->exec("UPDATE bien SET loyer_de_base = 4500000 WHERE loyer_de_base IS NULL");
    echo "✅ Valeurs de test configurées pour les loyers de base (4 500 000 GNF par défaut).\n";

} catch (PDOException $e) {
    echo "❌ Erreur de base de données : " . $e->getMessage() . "\n";
}
