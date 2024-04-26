# GenRapports

### 1.6.5
* FIX - Remove module descriptor url_last_version

### 1.6.4
* FIX - Correction numéro module fichiers .langs

### 1.6.3
* FIX - Correction fuseaux horaires
* FIX - Correction date de fin d'année si année bissextile
* FIX - Correction doublon t.import_key SQL

### 1.6.2
* FIX - Correction des rapports de date à date (les valeurs ne prennent plus en compte les mois entiers)
* FIX - Corrections divers warning PHP

### 1.6.1
* FIX - compatibilité v18 ExportCsv
* FIX - revue et modification de la fonction tableau_resultat
	- Correction des rapports de date à date (les valeurs ne prennent plus en compte les mois entiers)
	- Correction des rapports qui chevauchent des années (les valeurs de N ne sont plus reportées dans N+1)
* MAJ - Amélioration graphique

### 1.6.0 (14/10/2023)
* FIX - Modif index.php pour compatibilité v18

### 1.5.9 (23/05/2023)
* MAJ - Mise à jour descripteur module

### 1.5.8 (28/02/2023)
* MAJ - Suppression du compte 5124% du groupe 282 (emprunts). Il sera intégré avec 512% dans le groupe 133 "disponibilités"

### 1.5.7 (22/02/2023)
* FIX - Corrections ordres des requêtes SQL et numéros de compte

### 1.5.6 (29/09/2022)
* MAJ - Ajouts comptes 4383 4283

### 1.5.5 (29/09/2022)
* MAJ - Corrections droits pour export user

### 1.5.4 (29/09/2022)
* MAJ - Modification permissions & numéro module, pensez à verifier les droits utilisateurs

### 1.5.3 (01/06/2022)
* FIX - Correction affichage bornage date sur la même année

### 1.5.2 (01/06/2022)
* NEW - Affichage mises à jour pages modules

### 1.5.1 (01/03/2021)
* FIX - Modifications des appels aux fichiers css et js 

### 1.5 (01/03/2021)
* FIX - Insertion des variables dans le fichier principal 

### 1.4 (01/02/2021)
* FIX - Compatibilité v14 / v15 
* FIX - Meilleure méthode de surcharge des classes

### 1.3 (12/01/2021)
* FIX - Corrections de bugs concernant les requètes sql 
* FIX - Corrections de bugs concernant les libellés des balances auxiliaires 

### 1.2 (06/12/2021)
* NEW - Ajout d'une option pour determiner le nombre de chiffres que contiennent les numéros de compte
* NEW - Utilisation du système de traductions

### 1.1.1 (30/04/2021)
* MAJ - Modification pour mettre à jour les comptes en fonction de leur valeur (update_bilan());
* FIX - Mise en commentaire de la partie sauvegarde et à nouveaux, dev à valider pour 1.1

### 1.1 (31/03/2021)
* MAJ - Duplication de la class Bookkeeping afin de la modifier et s'en servir sans toucher au core de dolibarr
DEV - Copie des lignes bookkeeping dans la table llx_accounting_bookkeeping_lock en mode sauvegarde, fonction cachée, developpement en cours
DEV - Création automatique de la table llx_accounting_bookkeeping_lock avec les nouveaux champs suivants:
	* lock_id : un identifiant qui permettra de le retrouver via une requete sql simple
		* Si datestart et dateend meme_annee = lockid = lock_annee
		* Si datestart et dateend meme_annee = lockid = lock_anneestart_anneeend
	* lock_datestart : date début définie lors du blocage des lignes
	* lock_datestop : date fin définie lors du blocage des lignes
	* lock_date : date du blocage
	* lock_user : utilisateur ayant réalisé la manipulation
