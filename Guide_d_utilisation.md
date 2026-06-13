# Guide d’Utilisation Multi-Rôles – GESTLOYER

Ce manuel d’utilisation détaille le parcours de chaque profil utilisateur (Administrateur, Gestionnaire, Comptable, Propriétaire, Locataire) sur la plateforme GESTLOYER, illustré par les captures d'écran réelles de l'application.

---

## 1. Introduction & Espace d'Accès

La plateforme GESTLOYER est une solution moderne de gestion locative immobilière, conçue pour simplifier les interactions entre l'agence de gestion, les propriétaires et les locataires. Elle propose des tableaux de bord spécialisés et des fonctionnalités adaptées à chaque rôle.

### 1.1 Portail Public (Landing Page)
La page d'accueil présente les avantages de la plateforme (automatisation des paiements, coffre-fort numérique, gestion transparente des incidents). Elle permet également aux nouveaux utilisateurs de s'inscrire.

![Page d'accueil publique de GESTLOYER (Haut)](../Documentation%20compl%C3%A8te%20en%20symfony/Document%20en%20symfony/Capture%20d%E2%80%99%C3%A9cran_13-6-2026_1043_127.0.0.1.jpeg)
![Présentation des services](../Documentation%20compl%C3%A8te%20en%20symfony/Document%20en%20symfony/Capture%20d%E2%80%99%C3%A9cran_13-6-2026_1055_127.0.0.1.jpeg)

### 1.2 Connexion (Login)
L'écran de connexion centralisé permet à tous les profils de s'identifier à l'aide de leur adresse e-mail et de leur mot de passe sécurisé.

![Interface de Connexion unique](../Documentation%20compl%C3%A8te%20en%20symfony/Document%20en%20symfony/Capture%20d%E2%80%99%C3%A9cran_13-6-2026_10528_127.0.0.1.jpeg)

### 1.3 Inscription des Locataires & Propriétaires
Les nouveaux locataires et propriétaires peuvent créer un compte directement depuis le portail public en renseignant leurs informations personnelles et de contact.

![Formulaire de création de compte](../Documentation%20compl%C3%A8te%20en%20symfony/Document%20en%20symfony/Capture%20d%E2%80%99%C3%A9cran_13-6-2026_10441_127.0.0.1.jpeg)

---

## 2. Guide de l'Administrateur (ROLE_ADMIN)

L'administrateur dispose d'une vue globale sur l'intégralité du parc et gère la configuration stratégique de l'agence, la sécurité des accès et les équipes opérationnelles.

### 2.1 Tableau de Bord Analytique Administrateur
Ce tableau de bord fournit des indicateurs clés en temps réel (KPIs) :
*   **Taux d'occupation global** : Pourcentage de biens actuellement sous bail actif par rapport au total.
*   **Loyers encaissés** : Volume financier total collecté en GNF pour l'année en cours.
*   **Impayés en attente** : Montant cumulé des retards de paiement de loyers.
*   **Incidents** : Nombre de tickets de maintenance ou de pannes en cours de traitement.

![Tableau de Bord Analytique de l'Administrateur](../Documentation%20compl%C3%A8te%20en%20symfony/Document%20en%20symfony/Capture%20d%E2%80%99%C3%A9cran_13-6-2026_10556_127.0.0.1.jpeg)

### 2.2 Paramètres de l'Agence & Archives
L'administrateur gère l'identité légale de l'agence, l'IBAN de versement, et accède aux historiques d'audit de sécurité (connexions, déconnexions et actions des utilisateurs) pour assurer la traçabilité des opérations.

![Interface d'Administration complet](../Documentation%20compl%C3%A8te%20en%20symfony/Document%20en%20symfony/Capture%20d%E2%80%99%C3%A9cran_13-6-2026_1070_127.0.0.1.jpeg)
![Formulaire de configuration du profil de l'Agence](../Documentation%20compl%C3%A8te%20en%20symfony/Document%20en%20symfony/Capture%20d%E2%80%99%C3%A9cran_13-6-2026_10756_127.0.0.1.jpeg)
![Gestion de l'équipe opérationnelle](../Documentation%20compl%C3%A8te%20en%20symfony/Document%20en%20symfony/Capture%20d%E2%80%99%C3%A9cran_13-6-2026_10816_127.0.0.1.jpeg)
![Journal d'audit de sécurité](../Documentation%20compl%C3%A8te%20en%20symfony/Document%20en%20symfony/Capture%20d%E2%80%99%C3%A9cran_13-6-2026_10843_127.0.0.1.jpeg)

---

## 3. Guide du Gestionnaire (ROLE_GESTIONNAIRE)

Le gestionnaire est le pivot opérationnel de la relation client. Il gère l'état du parc immobilier, les contrats de bail et assure la coordination terrain.

### 3.1 Tableau de Bord du Gestionnaire
Permet un pilotage opérationnel des nouveaux baux, des départs locataires, et l'accès direct aux modules patrimoine et contrats.

![Tableau de Bord du Gestionnaire](../Documentation%20compl%C3%A8te%20en%20symfony/Document%20en%20symfony/Capture%20d%E2%80%99%C3%A9cran_13-6-2026_102255_127.0.0.1.jpeg)

### 3.2 Gestion du Patrimoine (Biens)
Le gestionnaire répertorie chaque bien avec sa localisation, sa surface et son loyer de base. Il peut rapidement ajouter un nouveau bien via un formulaire intuitif.

![Grille de gestion du patrimoine](../Documentation%20compl%C3%A8te%20en%20symfony/Document%20en%20symfony/Capture%20d%E2%80%99%C3%A9cran_13-6-2026_102131_127.0.0.1.jpeg)
![Formulaire d'ajout de bien](../Documentation%20compl%C3%A8te%20en%20symfony/Document%20en%20symfony/Capture%20d%E2%80%99%C3%A9cran_13-6-2026_102347_127.0.0.1.jpeg)

### 3.3 Gestion des Contrats de Location (Baux)
Le gestionnaire rédige et active les contrats de location. Il associe un locataire à un bien disponible et définit la date de prise d'effet ainsi que les conditions financières.

![Liste des contrats](../Documentation%20compl%C3%A8te%20en%20symfony/Document%20en%20symfony/Capture%20d%E2%80%99%C3%A9cran_13-6-2026_102534_127.0.0.1.jpeg)
![Formulaire de rédaction de contrat](../Documentation%20compl%C3%A8te%20en%20symfony/Document%20en%20symfony/Capture%20d%E2%80%99%C3%A9cran_13-6-2026_102320_127.0.0.1.jpeg)

### 3.4 Suivi des Paiements (Vue Gestionnaire)
Le gestionnaire suit l'avancement des règlements pour vérifier l'adéquation avec les contrats actifs.

![Suivi des paiements](../Documentation%20compl%C3%A8te%20en%20symfony/Document%20en%20symfony/Capture%20d%E2%80%99%C3%A9cran_13-6-2026_102411_127.0.0.1.jpeg)

---

## 4. Guide du Comptable (ROLE_COMPTABLE)

Le comptable supervise tous les flux financiers de l'agence : encaissement des loyers, gestion des relances et reversements des loyers aux propriétaires.

### 4.1 Tableau de Bord Analytique Comptable
Ce tableau présente les flux mensuels, le montant global recouvré et les impayés en attente de traitement.

![Tableau de Bord Comptable](../Documentation%20compl%C3%A8te%20en%20symfony/Document%20en%20symfony/Capture%20d%E2%80%99%C3%A9cran_13-6-2026_102021_127.0.0.1.jpeg)

### 4.2 Enregistrement & Validation des Paiements
Le comptable valide la réception des loyers (par Mobile Money, Espèces, Virement) et déclenche l'émission automatique de la quittance de loyer au format PDF pour le locataire.

![Validation des encaissements](../Documentation%20compl%C3%A8te%20en%20symfony/Document%20en%20symfony/Capture%20d%E2%80%99%C3%A9cran_13-6-2026_10211_127.0.0.1.jpeg)

---

## 5. Guide du Propriétaire (ROLE_PROPRIETAIRE)

L'espace propriétaire permet de suivre la performance financière de ses biens immobiliers mis en gestion.

### 5.1 Tableau de Bord du Propriétaire
Permet d'avoir une vision claire sur :
*   **Loyers encaissés nets** : Montant total versé par l'agence sur son compte bancaire.
*   **Taux d'occupation de ses biens** : Suivi de la vacance de ses appartements.
*   **Interventions et incidents en cours** : Alertes sur les réparations nécessaires.

![Tableau de Bord du Propriétaire](../Documentation%20compl%C3%A8te%20en%20symfony/Document%20en%20symfony/Capture%20d%E2%80%99%C3%A9cran_13-6-2026_10298_127.0.0.1.jpeg)

### 5.2 Gestion de son Profil & Coordonnées
Le propriétaire peut mettre à jour ses coordonnées bancaires et personnelles à tout moment pour garantir les virements mensuels de l'agence.

![Formulaire de mise à jour de profil](../Documentation%20compl%C3%A8te%20en%20symfony/Document%20en%20symfony/Capture%20d%E2%80%99%C3%A9cran_13-6-2026_102042_127.0.0.1.jpeg)

---

## 6. Guide du Locataire (ROLE_LOCATAIRE)

Le locataire dispose d'un espace personnel complet pour suivre son bail, télécharger ses quittances, et interagir avec l'agence pour la maintenance.

### 6.1 Tableau de Bord du Locataire
Il regroupe les informations de son bail (loyer de base, charges), l'historique de ses paiements, et les biens actuellement disponibles à la location.

![Tableau de Bord Locataire](../Documentation%20compl%C3%A8te%20en%20symfony/Document%20en%20symfony/Capture%20d%E2%80%99%C3%A9cran_13-6-2026_104032_127.0.0.1.jpeg)

### 6.2 Déclaration d'Incidents techniques
En cas de panne ou de dégradation (fuite d'eau, électricité...), le locataire remplit un formulaire de déclaration d'incident, spécifie la priorité (Faible, Normal, Urgent, Critique) pour alerter le gestionnaire.

![Formulaire de déclaration d'incident](../Documentation%20compl%C3%A8te%20en%20symfony/Document%20en%20symfony/Capture%20d%E2%80%99%C3%A9cran_13-6-2026_104124_127.0.0.1.jpeg)

### 6.3 Configuration du Profil & Préférences de Langue / Thème
Tous les utilisateurs, notamment le locataire, peuvent basculer le thème (Clair / Sombre) et changer la langue de l'interface (Français / Anglais).

![Page de gestion du profil personnel](../Documentation%20compl%C3%A8te%20en%20symfony/Document%20en%20symfony/Capture%20d%E2%80%99%C3%A9cran_13-6-2026_10635_127.0.0.1.jpeg)

---

Fin du document.
