<?php
// Script pour mettre à jour les fichiers Twig avec les traductions

$files = [
    'templates/admin/parametres.html.twig' => [
        "Profil de l'Agence" => "{{ 'settings.nav.profil_agence'|trans }}",
        "Archives" => "{{ 'settings.nav.archives'|trans }}",
        "Comptables & Gestionnaires" => "{{ 'settings.nav.collaborateurs'|trans }}",
        "Messagerie" => "{{ 'settings.nav.messagerie'|trans }}",
        "Historiques" => "{{ 'settings.nav.historiques'|trans }}",
        "Apparence & Langue" => "{{ 'settings.nav.apparence'|trans }}",
        "À propos & Manuel" => "{{ 'settings.nav.about'|trans }}",

        "Configurez les informations légales et visuelles de votre agence. Ces données apparaîtront sur les quittances." => "{{ 'settings.agence.desc'|trans }}",
        "Informations Générales" => "{{ 'settings.agence.general'|trans }}",
        "Coordonnées de Contact" => "{{ 'settings.agence.contact'|trans }}",
        "Informations Légales & Bancaires" => "{{ 'settings.agence.legal'|trans }}",
        "Enregistrer le profil" => "{{ 'settings.agence.save'|trans }}",

        "Aucun utilisateur inactif pour ce rôle." => "{{ 'settings.archives.empty_role'|trans }}",

        "Vue d'ensemble des collaborateurs clés qui traitent les opérations (contrats, paiements, incidents)." => "{{ 'settings.collab.desc'|trans }}",
        "Voir gestionnaires" => "{{ 'settings.collab.btn_gestionnaires'|trans }}",
        "Voir comptables" => "{{ 'settings.collab.btn_comptables'|trans }}",
        "Aucun collaborateur actif (gestionnaire/comptable) pour le moment." => "{{ 'settings.collab.empty'|trans }}",

        "Envoyer un message à tous, à une personne, ou au groupe des locataires en retard de paiement." => "{{ 'settings.msg.desc'|trans }}",
        "<label class=\"block text-sm font-medium text-slate-300 mb-2\">Cible</label>" => "<label class=\"block text-sm font-medium text-slate-300 mb-2\">{{ 'settings.msg.target'|trans }}</label>",
        "Diffusion totale (tous les locataires)" => "{{ 'settings.msg.target_all'|trans }}",
        "Par personne (email)" => "{{ 'settings.msg.target_one'|trans }}",
        "Groupe (locataires en retard de paiement)" => "{{ 'settings.msg.target_late'|trans }}",
        "Email destinataire" => "{{ 'settings.msg.target_email'|trans }}",
        "<label class=\"block text-sm font-medium text-slate-300 mb-2\">Sujet</label>" => "<label class=\"block text-sm font-medium text-slate-300 mb-2\">{{ 'settings.msg.subject'|trans }}</label>",
        "Rappel de paiement / Information importante…" => "{{ 'settings.msg.subject_placeholder'|trans }}",
        "<label class=\"block text-sm font-medium text-slate-300 mb-2\">Message</label>" => "<label class=\"block text-sm font-medium text-slate-300 mb-2\">{{ 'settings.msg.message'|trans }}</label>",
        "Envoyer le message" => "{{ 'settings.msg.send'|trans }}",

        "Historique des connexions / déconnexions" => "{{ 'settings.hist.conn_title'|trans }}",
        "Suivi des utilisateurs qui se connectent et se déconnectent." => "{{ 'settings.hist.conn_desc'|trans }}",
        "Date" => "{{ 'settings.hist.col_date'|trans }}",
        "Utilisateur" => "{{ 'settings.hist.col_user'|trans }}",
        "Action" => "{{ 'settings.hist.col_action'|trans }}",
        "IP" => "{{ 'settings.hist.col_ip'|trans }}",
        "Aucun historique de connexion pour le moment." => "{{ 'settings.hist.conn_empty'|trans }}",

        "Historique des actions utilisateurs" => "{{ 'settings.hist.act_title'|trans }}",
        "Qui a fait quoi dans l'administration." => "{{ 'settings.hist.act_desc'|trans }}",
        "Acteur" => "{{ 'settings.hist.col_actor'|trans }}",
        "Cible" => "{{ 'settings.hist.col_target'|trans }}",
        "Détails" => "{{ 'settings.hist.col_details'|trans }}",
        "Aucune action enregistrée pour le moment." => "{{ 'settings.hist.act_empty'|trans }}",

        "Historique imports / exports CSV" => "{{ 'settings.hist.csv_title'|trans }}",
        "Traçabilité des imports et exports de rapports CSV." => "{{ 'settings.hist.csv_desc'|trans }}",
        "Action CSV" => "{{ 'settings.hist.col_csv_action'|trans }}",
        "Aucun import/export CSV pour le moment." => "{{ 'settings.hist.csv_empty'|trans }}",

        "Apparence du panel" => "{{ 'settings.app.title'|trans }}",
        "Langue du système" => "{{ 'settings.app.lang_title'|trans }}",

        "À propos de GESTLOYER" => "{{ 'settings.about.title'|trans }}",
        "Version du système" => "{{ 'settings.about.version'|trans }}",
        "Licence" => "{{ 'settings.about.license'|trans }}",
        "Dernière mise à jour" => "{{ 'settings.about.updated'|trans }}",
        "GESTLOYER est une solution complète de gestion locative, développée avec Symfony 7 et API Platform, offrant des outils performants pour la gestion des biens, des contrats et des paiements." => "{{ 'settings.about.desc'|trans }}",
        "Manuel d'Utilisation" => "{{ 'settings.about.manual_title'|trans }}",
        "Guide rapide pour bien utiliser GESTLOYER :" => "{{ 'settings.about.manual_desc'|trans }}",
        "Demarrage : connectez-vous puis consultez le tableau de bord (biens, contrats, paiements, incidents)." => "{{ 'settings.about.manual_li1'|trans }}",
        "Démarrage : connectez-vous puis consultez le tableau de bord (biens, contrats, paiements, incidents)." => "{{ 'settings.about.manual_li1'|trans }}",
        "Utilisateurs : creez les comptes et attribuez les roles adaptes (admin, gestionnaire, comptable, etc.)." => "{{ 'settings.about.manual_li2'|trans }}",
        "Utilisateurs : créez les comptes et attribuez les rôles adaptés (admin, gestionnaire, comptable, etc.)." => "{{ 'settings.about.manual_li2'|trans }}",
        "Parametres agence : mettez a jour les informations legales (SIRET, IBAN, logo, contact)." => "{{ 'settings.about.manual_li3'|trans }}",
        "Paramètres agence : mettez à jour les informations légales (SIRET, IBAN, logo, contact)." => "{{ 'settings.about.manual_li3'|trans }}",
        "Exploitation : suivez les paiements, impayes, contrats et incidents de maintenance." => "{{ 'settings.about.manual_li4'|trans }}",
        "Exploitation : suivez les paiements, impayés, contrats et incidents de maintenance." => "{{ 'settings.about.manual_li4'|trans }}",
        "Télécharger le manuel" => "{{ 'settings.about.download'|trans }}",
        "Telecharger le manuel" => "{{ 'settings.about.download'|trans }}"
    ],

    'templates/admin/users/index.html.twig' => [
        "Gestion des Utilisateurs" => "{{ 'admin.users.title'|trans }}",
        "Créer, modifier, activer ou désactiver les comptes utilisateurs" => "{{ 'admin.users.subtitle'|trans }}",
        "Panel Admin" => "{{ 'settings.back'|trans }}", // already exists
        "Nouvel utilisateur" => "{{ 'admin.users.btn_add'|trans }}",
        "Rechercher par email..." => "{{ 'admin.users.search_placeholder'|trans }}",
        "Tous les rôles" => "{{ 'admin.users.filter_all'|trans }}",
        "Tout statut" => "{{ 'admin.users.filter_status'|trans }}",
        "Actifs" => "{{ 'admin.users.filter_active'|trans }}",
        "Inactifs" => "{{ 'admin.users.filter_inactive'|trans }}",
        "Filtrer" => "{{ 'admin.users.filter_btn'|trans }}",
        "Effacer" => "{{ 'admin.users.clear_btn'|trans }}",
        "Utilisateur" => "{{ 'admin.users.col_user'|trans }}",
        "Rôle" => "{{ 'admin.users.col_role'|trans }}",
        "Inscription" => "{{ 'admin.users.col_date'|trans }}",
        "Statut" => "{{ 'admin.users.col_status'|trans }}",
        "Actions" => "{{ 'admin.users.col_actions'|trans }}",
        "Actif" => "{{ 'admin.users.status_active'|trans }}",
        "Inactif" => "{{ 'admin.users.status_inactive'|trans }}",
        "Éditer" => "{{ 'admin.users.btn_edit'|trans }}",
        "Désactiver" => "{{ 'admin.users.btn_deactivate'|trans }}",
        "Activer" => "{{ 'admin.users.btn_activate'|trans }}",
        "Reset MDP" => "{{ 'admin.users.btn_reset'|trans }}",
        "Archiver" => "{{ 'admin.users.btn_archive'|trans }}",
        "Aucun utilisateur trouvé" => "{{ 'admin.users.empty'|trans }}",
        "Modifiez vos critères de recherche" => "{{ 'admin.users.empty_desc'|trans }}"
    ],

    'templates/admin/index.html.twig' => [
        "Aucun incident récent" => "{{ 'admin.aucun_incident'|trans }}",
        "Aucun paiement récent" => "{{ 'admin.aucun_paiement'|trans }}",
        "Revenus cumulés sur les 6 derniers mois" => ""
    ]
];

foreach ($files as $filepath => $replacements) {
    if (file_exists($filepath)) {
        $content = file_get_contents($filepath);

        // Sort by length desc to replace longer strings first
        uksort($replacements, function($a, $b) {
            return strlen($b) - strlen($a);
        });

        foreach ($replacements as $search => $replace) {
            // Avoid double replacements (e.g., if already replaced)
            if (strpos($content, $replace) === false) {
                $content = str_replace($search, $replace, $content);
            }
        }
        
        file_put_contents($filepath, $content);
        echo "Updated: $filepath\n";
    } else {
        echo "File not found: $filepath\n";
    }
}
