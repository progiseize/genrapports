# GenRapports

[comment]: <> (TODO)

***
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
* DEV - Copie des lignes bookkeeping dans la table llx_accounting_bookkeeping_lock en mode sauvegarde, fonction cachée, developpement en cours
* DEV - Création automatique de la table llx_accounting_bookkeeping_lock avec les nouveaux champs suivants:
	* lock_id : un identifiant qui permettra de le retrouver via une requete sql simple
		* Si datestart et dateend meme_annee = lockid = lock_annee
		* Si datestart et dateend meme_annee = lockid = lock_anneestart_anneeend
	* lock_datestart : date début définie lors du blocage des lignes
	* lock_datestop : date fin définie lors du blocage des lignes
	* lock_date : date du blocage
	* lock_user : utilisateur ayant réalisé la manipulation